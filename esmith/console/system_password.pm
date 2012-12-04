package esmith::console::system_password;
use esmith::util;
use Locale::gettext;
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
    return if ($db->get_value('PasswordSet') eq 'yes');
    #------------------------------------------------------------
    INITIAL_PASSWORD:
    #------------------------------------------------------------

    my $rc;
    my $choice;
    my $choice1;
    my $choice2;

    ($rc, $choice1) = $console->password_page
        (
         title => gettext("Choose administrator password"),
         text  =>
         gettext("Welcome to the server console!") .
         "\n\n" .
         gettext("You will now be taken through a sequence of screens to perform basic networking configuration on this server.") .
         "\n\n" .
         gettext("You can make your selections in each screen using the Arrow and Tab keys. At any point, if you select Back you will be returned to the previous screen.") .
         "\n\n" .
         gettext("Before you start, you must first choose the administrator password for your system and enter it below. You will not see the password as you enter it."),
        );

    unless ($rc == 0)
    {
        ($rc, $choice) = $console->message_page
            (
             title   => gettext("Administrator password not set"),
             text    => gettext("Sorry, you must set the administrator password now."),
            );

        goto INITIAL_PASSWORD;
    }

    unless ($choice1 =~ /^([ -~]+)$/)
    {
        ($rc, $choice) = $console->message_page
            (
             title   => gettext("Unprintable characters in password"),
             text    => gettext("The password must contain only printable characters."),
            );

        goto INITIAL_PASSWORD;
    }

    use Crypt::Cracklib;

    #--------------------------------------------------------
    # These are just to ensure that xgettext knows about the
    # Cracklib strings.
    # Note the extra space here and in the gettext call below. This
    # allows the French localization to properly generate qu'il
    gettext("it is based on your username");
    gettext("it is based upon your password entry");
    gettext("it is derived from your password entry");
    gettext("it is derivable from your password entry");
    gettext("it is too short");
    gettext("it is all whitespace");
    gettext("it is too simplistic/systematic");
    gettext("it is based on a dictionary word");
    gettext("it is based on a (reversed) dictionary word");
    gettext("it does not contain numbers");
    gettext("it does not contain uppercase characters");
    gettext("it does not contain lowercase characters");
    gettext("it does not contain special characters");
    #--------------------------------------------------------

    my $strength = $db->get_prop("passwordstrength", "Admin");
    my $reason = esmith::util::validatePassword($choice1,$strength);

    # Untaint return data from cracklib, so we can use it later. We
    # trust the library, so we accept anything.
    $reason =~ /(.+)/; $reason = $1;
    $reason ||= gettext("Software error: password check failed");
    unless ($reason eq 'ok')
    {
        ($rc, $choice) = $console->yesno_page
            (
             title => gettext("Bad Password Choice"),
             text  =>
             gettext("The password you have chosen is not a good choice, because ") .
             gettext($reason) . "." .
             "\n\n" .
             gettext("Do you wish to choose a better one?"),
            );

        goto INITIAL_PASSWORD if ($rc == 0);
    }

    ($rc, $choice2) = $console->password_page
        (
         title   => gettext("Choose administrator password"),
         text    => gettext("Please type your administrator password again to verify."),
        );

    unless ($rc == 0)
    {
        ($rc, $choice) = $console->message_page
            (
             title => gettext("Administrator password not set"),
             text  => gettext("Sorry, you must set the administrator password now."),
            );

        goto INITIAL_PASSWORD;
    }

    if ($choice1 ne $choice2)
    {
        ($rc, $choice) = $console->message_page
            (
             title => gettext("Passwords do not match"),
             text  => gettext("The two passwords did not match"),
            );

        goto INITIAL_PASSWORD;
    }

    #--------------------------------------------------
    # Set system password
    #--------------------------------------------------

    esmith::util::setUnixSystemPassword ($choice1);

    my $old = $db->get_value('UnsavedChanges');
    $db->set_value('PasswordSet', 'yes');
    $db->set_value('UnsavedChanges', $old);
}

1;

