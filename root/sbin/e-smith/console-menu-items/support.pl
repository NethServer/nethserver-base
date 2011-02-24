package esmith::console::support;
use strict;
use warnings;
use esmith::console;
use Locale::gettext;

sub new
{
    my $class = shift;
    my $self = {
		    name => gettext("View support and licensing information"),
		    order => 60,
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
# SUPPORT:
#------------------------------------------------------------
    my ($self, $console, $db) = @_;
    my $licenses = esmith::util::getLicenses();
    # Untaint license text before passing to screen
    ($licenses) = ($licenses =~ /(.*)/s);

    my ($rc, $choice) =
        $console->whiptail ("--title", gettext("Mitel Networks Corporation support and licensing information"),
                          "--scrolltext",
                          "--msgbox",
                          "**********************************************************************" .
                          "\n" .
                          gettext("You can scroll through this document using the up and down arrow keys or the Page Up and Page Down keys.") .
                          "\n" .
                          "**********************************************************************" .
                          "\n\n" .
                          $licenses,
                          esmith::console::SCREEN_ROWS, esmith::console::SCREEN_COLUMNS
                         );
}

return new esmith::console::support;
