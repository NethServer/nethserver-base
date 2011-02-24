package esmith::console::perform_restore;
use strict;
use warnings;
use esmith::ConfigDB;
use esmith::console;
use Locale::gettext;

sub new
{
    my $class = shift;
    my $self = {};
    bless $self, $class;
    return $self;
}

sub doit
{
    my ($self, $console, $db) = @_;
    return if ($db->get_value('PasswordSet') eq 'yes'); # Too late to do a restore
    my ($rc, $choice) = $console->yesno_page
        (
         title   => gettext("Restore From Backup"),
	 defaultno => 1,
         text =>
         gettext("Do you wish to restore from backup?"),
        );
    return unless $rc == 0;
    mkdir("/mnt/bootstrap-console-backup");
    system("/etc/init.d/messagebus", "start");
    system("/etc/init.d/haldaemon", "start");
    INITIATE_RESTORE:
    ($rc, $choice) = $console->yesno_page
        (
         title   => gettext("Insert media containing backup"),
         left => gettext("Next"),
         right => gettext("Cancel"),
         text =>
         gettext("Insert memory stick or CDROM containing your backup file, then hit the enter key."),
        );
    unless ($rc == 0) {
        system("/etc/init.d/haldaemon", "stop");
        system("/etc/init.d/messagebus", "stop");
        rmdir("/mnt/bootstrap-console-backup");
        return;
    }
    sleep(3);
    my @dirs;
    @dirs = ();
    foreach my $udi (qx(hal-find-by-property --key volume.fsusage --string filesystem)) {
        $udi =~ m/^(\S+)/;
        my $is_mounted = qx(hal-get-property --udi $1 --key volume.is_mounted);

        if ($is_mounted eq "false\n") {
            my $blkdev = qx(hal-get-property --udi $1 --key block.device);
            $blkdev =~ m/^(\S+)/;
            push @dirs, $1;
        }
    }
    unless ($dirs[0])
    {
	($rc, $choice) = $console->message_page
	    (
	     title   => gettext("Backup medium not found"),
	     right => "Try again",
	     text =>
	     gettext("No removable media or device found"),
	    );
	goto INITIATE_RESTORE;
    }
    my $device = $dirs[0];
    if (defined $dirs[1])
    {
	my $count=1;
# FIXME use better regexp
	my @args = map { /(.*)/; $count++ . '.' => $1 } @dirs;

	my ($rc, $choice) = $console->menu_page
        (
	    title => gettext("Choose device to restore from"),
	    text  => gettext("Please select which device contains the backup file you wish to restore from."),
	    argsref => \@args,
	    left => gettext("Cancel"),
	    right => gettext("OK"),
        );
	goto INITIATE_RESTORE unless ($rc == 0);
        my %args_hash = ( @args );
        $device = $args_hash{$choice};
    }
    system("/bin/mount", "$device", "/mnt/bootstrap-console-backup");
    sleep(1);

    unless (-f "/mnt/bootstrap-console-backup/smeserver.tgz")
    {
        system("/bin/umount", "$device");
	($rc, $choice) = $console->message_page
	    (
	     title   => gettext("Backup file not found"),
	     right => "Try again",
	     text =>
	     gettext("No backup file found"),
	    );
	goto INITIATE_RESTORE;
    }
    use File::stat;
    my $st = stat("/mnt/bootstrap-console-backup/smeserver.tgz");
    my $size = $st->size;
    
    ($rc, $choice) = $console->yesno_page
        (
         title => gettext("Start restore from backup"),
         text  =>
         gettext("Backup file found:") . " smeserver.tgz ($device) " .
         gettext("size") . " $size " . gettext("bytes") .
         "\n\n" .
         gettext("Do you wish to restore from this file?"),
        );
    unless ($rc == 0) {
        system("/bin/umount", "$device");
        goto INITIATE_RESTORE;
    }
    system("/sbin/e-smith/signal-event", "pre-restore");
    system("(cd / ; cat /mnt/bootstrap-console-backup/smeserver.tgz |
	pv -n -s $size |
	gunzip |
	tar xf - > /dev/null ) 2>&1 |
	dialog --backtitle 'Restoring data' --guage 'Progress' 7 70");
    $db->set_prop("bootstrap-console", "ForceSave", "yes");
    system("/bin/umount", "$device");
    system("/etc/init.d/haldaemon", "stop");
    system("/etc/init.d/messagebus", "stop");
    rmdir("/mnt/bootstrap-console-backup");
}

#use esmith::console;
#esmith::console::perform_restore->new->doit(esmith::console->new,
# esmith::ConfigDB->open);
1;

