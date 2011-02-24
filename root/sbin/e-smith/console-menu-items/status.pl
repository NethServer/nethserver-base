package esmith::console::status;
use strict;
use warnings;
use esmith::console;
use Locale::gettext;

sub new
{
    my $class = shift;
    my $self = {
		    name => gettext("Check status of this server"),
		    order => 10,
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
# STATUS:
#------------------------------------------------------------
    my ($self, $console, $db) = @_;
    use POSIX qw(strftime);
    my $today = strftime "%A %B %e, %Y", localtime;

    unless (open(UPTIME, "</proc/uptime"))
    {
	warn("Could not open /proc/uptime: $!");
	return;
    }
    my $seconds = <UPTIME>;
    close UPTIME or warn("Could not close /proc/uptime: $!");

    # Select and untaint seconds
    $seconds =~ /(\d+)/;
    $seconds = $1;

    my $days = int ($seconds / 86400);
    $seconds = $seconds % 86400;

    my $hours = int ($seconds / 3600);
    $seconds = $seconds % 3600;

    my $minutes = int ($seconds / 60);
    $seconds = $seconds % 60;

    my ($rc, $choice) = $console->screen
        (
         "--title",  gettext("Status of this server as of") . " " . $today,

         "--msgbox", gettext("This server has been running for") . " " .
         $days    . " " . gettext("days")  . ", " .
         $hours   . " " . gettext("hours") . ", " .
         $minutes . " " . gettext("minutes"),
         7, esmith::console::SCREEN_COLUMNS
        );
}

return new esmith::console::status;
