package esmith::console::serverManager;
use strict;
use warnings;
use esmith::console;
use Locale::gettext;

sub new
{
    my $class = shift;
    my $self = {
		    name => gettext("Access server manager"),
		    order => 50,
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
#------------------------------------------------------------
# MANAGER:
#------------------------------------------------------------
    my $SystemName = $db->get_value('SystemName');
    my ($rc, $choice) = $console->yesno_page
        (
         title => gettext("Access server manager"),
         text  =>
         gettext("This option will start a text-mode browser to access the server manager from this console.  Normally you would access the server manager from a web browser at the following url:") .
         "\n\n" .
         "https://${SystemName}/server-manager/" .
         "\n\n" .
         gettext("You should only proceed if you are comfortable using a text-mode browser.  Note that you will be prompted for the administrator password in order to access the server manager.") .
         "\n\n" .
         gettext("NOTE: The 'q' key is used to quit from the text-mode browser.") .
         "\n\n" .
         gettext("Do you wish to proceed?"),
        );

    if ($rc == 0)
    {
	system(
                   "/usr/bin/links",
                   "http://localhost/server-manager"
	      );
    }
    $db->reload;
}

return new esmith::console::serverManager;
