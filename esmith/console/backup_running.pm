package esmith::console::backup_running;
use strict;
use warnings;
use esmith::ConfigDB;
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
    #-------------------------------------------------------------
    # check whether a backup in process and incomplete
    #-------------------------------------------------------------
    my $restore_db = esmith::ConfigDB->open_ro("/etc/e-smith/restore");
    return unless $restore_db;

    my $restore_state = $restore_db->get_prop('restore', 'state')
	|| 'idle';

    return unless ($restore_state eq 'running');
    my ($rc, $choice) = $console->message_page
        (
         title => gettext("Inconsistent system state"),
         text  =>
         gettext("********** Inconsistent system state detected ***********") .
         "\n\n" .
         gettext("The restoration of a system backup was running and incomplete at the time of the last reboot. The system should not be used in this state.") .
         "\n\n" .
         gettext("Consult the User Guide for further instructions."),
        );

    ($rc, $choice) = $console->yesno_page
        (
         title => gettext("System will be halted"),
         text  =>
         gettext("The server will now be halted.") .
         "\n\n" .
         gettext("Consult the User Guide for recovery instructions.") .
         "\n\n" .
         gettext("Do you wish to halt the system right now?"),
        );

    return unless ($rc == 0);

    system("/usr/bin/tput", "clear");
    system("/sbin/e-smith/signal-event", "halt");

    # A bit of a hack to avoid the console restarting before the
    # reboot takes effect.

    sleep(600);
}

1;

