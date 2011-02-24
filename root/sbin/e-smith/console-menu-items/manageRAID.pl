package esmith::console::manageDiskRedundancy;
use strict;
use warnings;
use esmith::console;
use Locale::gettext;

use Data::Dumper;

use constant DEBUG_MANAGE_RAID => 1;

sub new
{
    my $class = shift;
    my $self = {
		    name => gettext("Manage disk redundancy"),
		    order => 45,
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

sub doit
{
    my ($self, $console, $db) = @_;
    my ($rc, $choice);

    use POSIX qw(strftime);
    my $today = strftime "%A %B %e, %Y %H:%M:%S", localtime;
    my $title = gettext("Disk redundancy status as of") . " " . $today,
    my $text = gettext("Current RAID status:") . "\n\n" . 
        join("", get_raid_status()) . "\n\n";

    my %devices = get_raid_details();

    warn $text if DEBUG_MANAGE_RAID;
    warn "devices: " . Dumper(\%devices) . "\n" if DEBUG_MANAGE_RAID;

    unless (scalar %devices)
    {
        $text = gettext("There are no RAID devices configured");
        ($rc, $choice) = $console->message_page(title => $title, text => $text);
        return;
    }

    my @unclean = ();
    my @recovering = ();
    my %used_disks = ();

    for my $dev (keys %devices)
    {
	$used_disks{$_}++ for (@{$devices{$dev}{UsedDisks}});

        if ($devices{$dev}{State} =~ /recovering/)
        {
            push @recovering, "$dev => " . $devices{$dev}{State};
            next;
        }

        next if ($devices{$dev}{State} eq "clean");

        push @unclean, "$dev => " . $devices{$dev}{State};
    }

    warn "used_disks: " . Dumper(\%used_disks) . "\n" if DEBUG_MANAGE_RAID;

    warn "unclean: @unclean\n" if DEBUG_MANAGE_RAID;

    warn "recovering: @recovering\n" if DEBUG_MANAGE_RAID;

    if (scalar @recovering)
    {
        $text .= gettext("A RAID resynchronization is in progress.");
        ($rc, $choice) = $console->message_page(title => $title, text => $text);
        return;
    }

    unless (scalar @unclean)
    {
        $text .= gettext("All RAID devices are in clean state");
        ($rc, $choice) = $console->message_page(title => $title, text => $text);
        return;
    }

    unless (scalar @unclean == scalar keys %devices)
    {
        $text .= gettext("Only some of the RAID devices are unclean.") . 
		"\n\n" .
                gettext("Manual intervention may be required.") . "\n\n";

        ($rc, $choice) = $console->message_page(title => $title, text => $text);
        return;
    }

    my %free_disks = map {$_ => 1} get_disks();

    delete $free_disks{$_} for keys %used_disks;

    warn "free_disks: " . Dumper(\%free_disks) . "\n" if DEBUG_MANAGE_RAID;

    my $disk_status = gettext("Current disk status:") . "\n\n";
    $disk_status .= gettext("Installed disks") . ": " . 
                    join(" ", get_disks()) . "\n";
    $disk_status .= gettext("Used disks") . ": " . 
                    join(" ", keys %used_disks) . "\n";
    $disk_status .= gettext("Free disks") . ": " . 
                    join(" ", keys %free_disks) . "\n";

    if (scalar keys %used_disks == 1 and scalar keys %free_disks == 0)
    {
        $text .= gettext("Your system only has a single disk drive installed or is using hardware mirroring. If you would like to enable software mirroring, please shut down, install a second disk drive (of the same capacity) and then return to this screen.");

        ($rc, $choice) = $console->message_page(title => $title, text => $text);
        return;
    }

    unless (scalar keys %free_disks == 1)
    {
        $text .= gettext("The free disk count must equal one.") .
                "\n\n" .
                gettext("Manual intervention may be required.") . "\n\n" .
                $disk_status;

        ($rc, $choice) = $console->message_page(title => $title, text => $text);
        return;
    }

    my @cmd = ("/sbin/e-smith/add_drive_to_raid", "-f", join("", keys %free_disks));

    $text = $disk_status . 
        "\n\n" . 
        gettext("There is an unused disk drive in your system. Do you want to add it to the existing RAID array(s)?") . 
	"\n\n" .
	gettext("WARNING: ALL DATA ON THE NEW DISK WILL BE DESTROYED!") .
	"\n"
        ;

    ($rc, $choice) = $console->yesno_page(title => $title, text => $text, defaultno => 1);
    return unless ($rc == 0);

    my $cmd_out = qx( @cmd 2>&1 );
    unless ($? == 0)
    {
        $text = gettext("The command failed:") . " @cmd" .
                "\n\n" . $cmd_out . "\n\n" .
                gettext("This configuration is not yet fully supported in these screens.");

        ($rc, $choice) = $console->message_page(title => $title, text => $text);
        return;
    }

}

sub get_raid_status
{
    die gettext("Couldn't open") . " /proc/mdstat:$!\n"
        unless (open(MDSTAT, "/proc/mdstat"));

    my @mdstat;

    while (<MDSTAT>)
    {
	push @mdstat, "$1\n" if (/(.*\w.*)/);
    }
    close MDSTAT;
    return @mdstat;
}

sub get_raid_details
{
    my @devices = ();

    die gettext("Couldn't call") . " mdadm: $!\n"
        unless open(MDADM, "/sbin/mdadm --detail --scan|");
   
    while (<MDADM>)
    {
        push @devices, $1 if ( m:ARRAY (/dev/md\d+): ) 
    }
    close MDADM;

    my %devices;

    for my $dev (@devices)
    {
        die gettext("Couldn't call") . " mdadm --detail $dev: $!\n" 
            unless open(MDADM, "/sbin/mdadm --detail $dev|");

        while ( <MDADM> )
        {
            if ( /\s*(.*)\s+:\s+(\d+)\s+\(.*\)\s*/ )
            {
                my ($key, $value) = ($1, $2);
                $key =~ s/\s//g;

		# Allow for different mdadm output formats for DeviceSize
                $key =~ s/UsedDevSize/DeviceSize/;

                $devices{$dev}{$key} = $value;
            }
            elsif ( /\s*(.*)\s+:\s+(.*)\s*/ )
            {
                my ($key, $value) = ($1, $2);
                $key =~ s/\s//g;
                $devices{$dev}{$key} = $value;
            }

            if ( m:\s+(\d+)\s+(\d+)\s+(\d+).*/dev/([\w\/]+): )
            {
                $devices{$dev}{$1} = $_;
                my $used_disk = $4;
                if (/(rd|ida|cciss|i2o)\//) {
                    $used_disk =~ s/p\d+$//;
                } else {
                    $used_disk =~ s/\d+//;
                }
                push (@{$devices{$dev}{UsedDisks}}, $used_disk);
            }
        }
        close MDADM;
    }

    return %devices;
}

sub get_partitions
{
    die gettext("Couldn't read") . " /proc/partitions: $!\n" 
        unless open (PARTITIONS, "/proc/partitions");

    my %parts;

    while (<PARTITIONS>)
    {
        if ( /\s+(\d+)\s+(\d+)\s+(\d+)\s+([\w\/]+)\s+/ )
        {
            my $name = $4;

            $parts{$name}{major} = $1;
            $parts{$name}{minor} = $2;
            $parts{$name}{blocks} = $3;
        }
    }
    close PARTITIONS;

    return %parts;
}

sub get_disks
{
    my %parts = get_partitions();

    my @disks;

    for (keys %parts)
    {
        push @disks, $_ unless (/[0-9]$/);
        push @disks, $_ if (/(rd|ida|cciss|i2o)\// && ! /p\d+$/);
    }

    return @disks;
}

return new esmith::console::manageDiskRedundancy;
