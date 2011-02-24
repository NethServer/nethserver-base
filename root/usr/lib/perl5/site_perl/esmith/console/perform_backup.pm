package esmith::console::perform_backup;
use strict;
use warnings;
use esmith::ConfigDB;
use esmith::console;
use esmith::util;
use Locale::gettext;
use esmith::Backup;
#use Filesys::DiskFree;
#use Sys::Filesystem;

sub new
{
    my $class = shift;
    my $self = {
		    name => gettext("Perform backup to USB device"),
		    order => 80,
		};
    bless $self, $class;
    return $self;
}

sub name
{ 
    return $_[0]->{name};
}

sub order
{
    return $_[0]->{order};
}

sub backup_size
{
    my $self = shift;

}

sub make_backup_callback
{
    my $device = shift;
    return sub {
        my $fh = shift;
        my @backup_list = esmith::Backup->restore_list;

        unless (open(DU, "-|"))
        {
            open(STDERR, ">/dev/null");
            exec qw(/usr/bin/du -sb), map { "/$_" } @backup_list;
        }
        my $backup_size = 0;
        while (<DU>)
        {
            next unless (/^(\d+)/);
            $backup_size += $1;
        }
        close DU;

        open(OLDSTDOUT, ">&STDOUT");
        unless (open(STDOUT, ">/mnt/bootstrap-console-backup/smeserver.tgz"))
        {
            return gettext("Could not create backup file on device").": $!\n";
        }

        open(OLDSTDERR, ">&STDERR");
        my $logger = open(STDERR, "|-");
        die "Can't fork: $!\n" unless defined $logger;

        unless ($logger)
        {
            exec qw(/usr/bin/logger -p local1.info -t console_backup);
        }

        my $status = 0;

        my $gzip = open(GZIP, "|-");
        return "could not run gzip" unless defined $gzip;
        unless ($gzip)
        {
            close $fh;
            exec "gzip", "-9";
        }

        my $pv = open(PV, "|-");
        return "could not run pv" unless defined $pv;
        unless ($pv)
        {
            open(STDOUT, ">&GZIP");
            close GZIP;
            open(STDERR, ">&$fh");
            exec qw(pv -i 0.2 -n -s), $backup_size
        }

        my $tar = fork;
        return "could not run tar" unless defined $tar;
        unless ($tar)
        {
            open(STDOUT, ">&PV");
            close PV;
            close GZIP;
            close $fh;
            chdir "/";
            exec qw(tar cf -), grep { -e $_ } @backup_list;
        }
        waitpid($tar, 0);
        warn "status from tar was $?\n" if $?;
        unless (close PV)
        {
            $status |= $! ? $! : $?;
            warn "status from pv is $status\n" if $status;
        }
        unless (close GZIP)
        {
            $status |= $! ? $! : $?;
            warn "status from gzip is $status\n" if $status;
        }

        open(STDOUT, ">&OLDSTDOUT");
        open(STDERR, ">&OLDSTDERR");
        close(OLDSTDERR);
        close(OLDSTDOUT);
        return $status ? gettext("Backup returned non-zero") : gettext("Success");
    };
}

sub doit
{
    my ($self, $console, $db) = @_;
    my @backup_list = esmith::Backup->restore_list;

    $ENV{PATH} = "/bin:/usr/bin";
    $ENV{HOME} = "/root";

    my ($rc, $choice) = $console->yesno_page
        (
         title   => gettext("Create Backup to USB disk"),
	 defaultno => 1,
         text =>
         gettext("Do you wish to create backup on USB device?"),
        );
    return unless $rc == 0;
    INITIATE_BACKUP:
    ($rc, $choice) = $console->yesno_page
        (
         title   => gettext("Insert media to use for backup"),
         left => gettext("Next"),
         right => gettext("Cancel"),
         text =>
         gettext("Insert memory stick or USB disk, then hit the enter key."),
        );
    return unless $rc == 0;
    sleep(3);
    my @dirs = ();
    my @labels = ();
    foreach my $udi (qx(hal-find-by-property --key volume.fsusage --string filesystem)) {
        $udi =~ m/^(\S+)/;
        my $is_mounted = qx(hal-get-property --udi $1 --key volume.is_mounted);

        if ($is_mounted eq "false\n") {
            my $blkdev = qx(hal-get-property --udi $1 --key block.device);
            $blkdev =~ m/^(\S+)/;
            push @dirs, $1;
        }
        if ($is_mounted eq "false\n") {
            my $vollbl = qx(hal-get-property --udi $1 --key volume.label);
            $vollbl =~ m/^(\S+)/;
            if ($vollbl =~ /^\s/) {$vollbl = 'nolabel';}
            chomp $vollbl;
            push @labels, lc($vollbl);
        }
    }
    unless ($dirs[0])
    {
	($rc, $choice) = $console->message_page
	    (
	     title   => gettext("Backup medium not found"),
	     right => gettext("Back"),
	     text =>
	     gettext("No removable media or device found"),
	    );
	goto INITIATE_BACKUP;
    }
    mkdir("/mnt/bootstrap-console-backup");

    my $device = $dirs[0];
    if (defined $dirs[1])
    {
	my $count=1;
	my @args = map { $count++ . '.' => $_ } @dirs;

	my ($rc, $choice) = $console->menu_page
        (
	    title => gettext("Choose device to use for backup"),
	    text  => ("@dirs \n @labels"),
	    argsref => \@args,
	    left => gettext("Cancel"),
	    right => gettext("OK"),
        );
	goto INITIATE_BACKUP unless ($rc == 0);
        my %args_hash = ( @args );
        $device = $args_hash{$choice};
    }
    system("/bin/mount", "$device", "/mnt/bootstrap-console-backup");

    use File::stat;
    my $st = stat("/mnt/bootstrap-console-backup/smeserver.tgz");
    if ($st)
    {
# TODO
# old backup exists - what do we want to do with it?
	my $size = $st->size;
	
    }

    $console->infobox(
           title => gettext("Preparing for backup"),
           text => gettext("Please stand by while the system is prepared for backup..."),
          );

    my $backup_size = 0;
    system("/sbin/e-smith/signal-event", "pre-backup");
    unless (open(DU, "-|"))
    {
	open(STDERR, ">/dev/null");
	exec qw(/usr/bin/du -sb), map { "/$_" } @backup_list;
    }
    while (<DU>)
    {
	next unless (/^(\d+)/);
	$backup_size += $1;
    }
    close DU;
 
    $console->gauge(make_backup_callback("/mnt/bootstrap-console-backup"), 'title' => gettext("Creating backup file"));

    system("/bin/umount", "/mnt/bootstrap-console-backup");
    rmdir("/mnt/bootstrap-console-backup");
    system("/sbin/e-smith/signal-event", "post-backup");
    ($rc, $choice) = $console->message_page
        (
         title   => gettext("Backup complete"),
         text =>
         gettext("Remove memory stick or USB disk, then hit the enter key."),
        );
}

#use esmith::console;
#esmith::console::perform_backup->new->doit(esmith::console->new,
# esmith::ConfigDB->open);
1;
