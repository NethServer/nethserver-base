package esmith::console::quitConsole;
use strict;
use warnings;
use Locale::gettext;

sub new
{
    my $class = shift;
    my $self = {
		    name => gettext("Exit from the server console"),
		    order => 100,
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
    if ( $db->get_value('UnsavedChanges') ne 'no' )
    {
	my ($rc, $choice) = $console->yesno_page
	    (
	     title   => gettext("*** THERE ARE UNACTIVATED CHANGES - QUIT ANYWAY? ***"),
	     text =>
	     gettext("Your configuration changes have been saved but have not yet been activated. This may result in unpredictable system behavior. We recommend that you complete the configuration process and activate the changes before exiting the console.") .
	     "\n\n" .
	     gettext("Are you sure you want to quit with unactivated changes?"),
	    );

	return unless ($rc == 0);
	}

    system("/usr/bin/tput", "clear");
    exit (0);
}

1;
