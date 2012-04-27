package esmith::console::save_config;
use Locale::gettext;
use esmith::console;
use strict;
use warnings;

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

    my $BootstrapConsole = $db->get_value('BootstrapConsole') || 'enabled';

    if($BootstrapConsole eq 'disabled') {
	return;
    }

    #------------------------------------------------------------
 SAVE_CONFIG:
    #------------------------------------------------------------

    # After saving config we don't need to run it again on the
    # next reboot.
    $db->set_value("BootstrapConsole", "disabled");

    $console->infobox(
           title => gettext("Activating configuration settings"),
           text => gettext("Please stand by while your configuration settings are activated ..."),
          );

    system("/sbin/e-smith/signal-event", 'bootstrap-console-save');
}

1;
