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

    #------------------------------------------------------------
 SAVE_CONFIG:
    #------------------------------------------------------------

    $console->infobox(
           title => gettext("Activating configuration settings"),
           text => gettext("Please stand by while your configuration settings are activated ..."),
          );

    system("/sbin/e-smith/signal-event", 'bootstrap-console-save');
}

1;
