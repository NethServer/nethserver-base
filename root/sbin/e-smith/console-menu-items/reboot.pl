package esmith::console::reboot;
use strict;
use warnings;
use esmith::console;
use Locale::gettext;

sub new
{
    my $class = shift;
    my $self = {
		    name => gettext("Reboot, reconfigure or shut down this server"),
		    order => 40,
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
#------------------------------------------------------------
# REBOOT_SHUTDOWN:
#------------------------------------------------------------
    my ($self, $console, $db) = @_;
    my @args =
        (
         gettext("Reboot"),     gettext("Reboot this server"),
	 gettext("Reconfigure"),   gettext("Reconfigure this server"),
         gettext("Shutdown"),   gettext("Shutdown this server"),
        );

    my ($rc, $choice) = $console->menu_page
        (
         title => gettext("Reboot, reconfigure or shutdown this server"),
         text  =>
         gettext("Please select whether you wish to reboot, reconfigure or shutdown. The process will start as soon as you make your selection.") .
         "\n\n" .
         gettext("If you have an older computer without power management, the shutdown process will perform a clean halt of all system services, but will not actually power off your computer. In this case, wait for the power down message and then shut off the power manually.") .
         "\n\n" .
         gettext("If you have changed your mind and do not want to reboot or shutdown, use the Tab key to select Cancel, then press Enter."),
         argsref => \@args,
         left => gettext("Cancel"),
         right => gettext("OK"),
        );

    return unless ($rc == 0);

    if ($choice eq gettext('Shutdown'))
    {
        system("/usr/bin/tput", "clear");
        system("/sbin/e-smith/signal-event", "halt");
    }
    elsif ($choice eq gettext('Reboot'))
    {
        system("/usr/bin/tput", "clear");
        system("/sbin/e-smith/signal-event", "reboot");
    }
    elsif ($choice eq gettext('Reconfigure'))
    {
        system("/usr/bin/tput", "clear");
	system("/sbin/e-smith/signal-event", "post-upgrade");
        system("/sbin/e-smith/signal-event", "reboot");
    }

    # A bit of a hack to avoid the console restarting before the
    # reboot takes effect.

    sleep(600);
}

return new esmith::console::reboot;
