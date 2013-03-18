package esmith::console::configure;
use strict;
use warnings;
use Locale::gettext;
use esmith::console;
use esmith::util::network qw(:all);
use esmith::db;
use esmith::ethernet;
use esmith::event;
use Net::IPv4Addr qw(:all);

our @adapters;
our $console;
our $db;
our $idb;
our $prevScreen;


sub new
{
    my $class = shift;
    my $self = {
		    name => gettext("Configure this server"),
		    order => 20,
		    bootstrap => 0,
		    @_,
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

#------------------------------------------------------------
# ethernetSelect()
# Choose appropriate Ethernet driver for the given interface
# Returns the selection method
#------------------------------------------------------------
sub ethernetSelect($$)
{
    my ($ifName, $confEntry) = @_;
    my $item = 0;

    if (scalar @adapters == 1)
    {
	# Internal, and there's only one, force name to green
	$ifName = 'green';
        my (undef, $driver, $hwaddr, undef) = split (/\s+/, $adapters[0], 4);
        $idb->set_prop($ifName, "role", "green", type => 'ethernet');
        $idb->set_prop($ifName, "hwaddr", $hwaddr);
        $db->set_value('UnsavedChanges', 'yes');
        # delete old interface entry
        if ($driver ne 'green') {
            my $i = $idb->get($driver);
            $i->delete();
        }


	return 'CHANGE';
    }

    my %tag2hwaddr;
    my %tag2name;
    my @args;
    my $default;

    if ( @adapters == 0 ) {

        my ($rc, $choice) = $console->message_page
               (
                title   => gettext("No network interfaces found"),
                text    => gettext("The installer can't continue because no network interfaces are installed or recognised. Please install at least one network interface."),
               );

        return 'NONIC';

    }

    foreach my $adapter ( @adapters )
    {
        my ($parameter, $driver, $hwaddr, $chipset) = split (/\s+/, $adapter, 4);
        chomp($chipset);

	my $tag = ++$item . ".";

	$tag2hwaddr{$tag} = $hwaddr;
	$tag2name{$tag} = $driver;

	my $display_name = ${driver} . ": " . ${chipset} . " - " . ${hwaddr}; 

	push(@args, $tag, substr($display_name, 0, 65));

    }

    #--------------------------------------------------------
    # These are just to ensure that xgettext knows about the
    # interface types.
    gettext("local");
    #--------------------------------------------------------

    my ($rc, $choice) = $console->menu_page
        (
         title => sprintf(gettext("Select %s network ethernet driver"),
                          gettext($ifName)),
	 default => $default,
         text  =>
         sprintf(gettext("You now need to select the proper driver for your " .
	      "%s network ethernet adapter. The server can attempt to do " .
	      "this automatically, or you can do it manually - either by " .
	      "specifying the model of your ethernet adapter or by " .
	      "directly choosing a driver.\n"), gettext($ifName)),
         argsref => \@args,
        );

    return 'CANCEL' unless ($rc == 0);
    
    $ifName = $tag2name{$choice};
    $idb->set_prop($ifName, "role", "green");
    $db->set_value('UnsavedChanges', 'yes');

    # rename to green

    my $new_name = 'green'; # new_name = role
    my $i = $idb->get($ifName);
    if ( defined ($i) ) {
        my %props = $i->props;
        $i->delete();
        $idb->set_prop($new_name, 'role', 'green', type => 'ethernet');
        $i = $idb->get($new_name);
        $i->reset_props(%props);
        $idb->set_prop($new_name,'device',$new_name);
    } else { # the card is not in the db
        my $g = $idb->green();
	if(defined($g)) {
	    $g->delete(); # delete ol green devnce
	}
        $idb->set_prop($new_name, 'role', 'green', type => 'ethernet');
        $idb->set_prop($new_name, 'hwaddr', $tag2hwaddr{$choice});
        $idb->set_prop($new_name, 'device', $new_name);
        $idb->set_prop($new_name, 'onboot', 'yes');
    }



    return 'CHANGE';
}

sub doit
{
    my $self = shift;
    $console = shift;
    $db = shift;
    $idb = shift;

    my $SystemName = $db->get_value('SystemName');
    my $DomainName = $db->get_value('DomainName');
    my ($rc, $choice);

    #------------------------------------------------------------
    CONFIGURE_MAIN:
    #------------------------------------------------------------
    return unless $console->run_screens( "CONFIGURE_MAIN" );

    # Refresh the db
    $db->reload;
    $idb->reload;

    # Probe to detect ethernet adapters
    @adapters = split(/\n/, esmith::ethernet::probeAdapters());

    #------------------------------------------------------------
    DOMAIN_NAME:
    #------------------------------------------------------------
{
    ($rc, $choice) = $console->input_page
        (
         title   => gettext("Primary domain name"),
         text    =>
         gettext("Please enter the primary domain name for your server.") .
         "\n\n" .
         gettext("This will be the default domain for your e-mail and web server. Virtual domains can be added later using the server manager."),
         value   => $DomainName
        );

    if ($rc != 0)
    {
	# If user cancelled, either loop or go back to main menu
	goto DOMAIN_NAME if $self->{bootstrap};
	return;
    }

    if ($choice)
    {
        if ($choice =~ /^([a-zA-Z0-9\-\.]+)$/)
        {
            $db->set_value('DomainName', $DomainName = lc($1));
            goto SYSTEM_NAME;
        }
    }
    else
    {
        $choice = '';
    }

    ($rc, $choice) = $console->tryagain_page
        (
         title  => gettext("Invalid domain name"),
         choice => $choice,
        );

    goto DOMAIN_NAME;
}

#------------------------------------------------------------
SYSTEM_NAME:
#------------------------------------------------------------

{
    my $oldSystemName = $SystemName;

    $oldSystemName = '' if ($oldSystemName eq 'mitel-networks-server');

    ($rc, $choice) = $console->input_page
        (
         title => gettext("Select system name"),
         text  =>
         gettext("Please enter the system name for your server.") .
         "\n\n" .
         gettext("You should select unique system names for each server.") .
         "\n\n" .
         gettext("The system name must start with a letter and can be composed of  letters, numbers and hyphens."),
         value   => $oldSystemName
        );

    goto DOMAIN_NAME unless ($rc == 0);

    if ($choice =~ /^([a-zA-Z][a-zA-Z0-9\-]*)$/)
    {
	$db->set_value('SystemName', $SystemName = lc($1));
	goto ETHERNET_LOCAL;

    }
    else
    {
        $choice = '';
    }

    ($rc, $choice) = $console->tryagain_page
        (
         title   => gettext("Invalid system name"),
         choice  => $choice,
        );

    goto SYSTEM_NAME;
}

# Display a dialog about how the module failed to load.
sub failed_to_load
{
    my $driver = shift;
    my ($rc, $choice) = $console->tryagain_page
        (
         title   => gettext("The specified driver failed to load."),
         choice  => $driver
        );
}

#------------------------------------------------------------
ETHERNET_LOCAL:
#------------------------------------------------------------

{
    my ($selectMode, $newDriver) = ethernetSelect('local', 'EthernetDriver1');

    goto ETHERNET_LOCAL     if ($selectMode eq 'CANCEL_MANUAL');

    goto SYSTEM_NAME        if ($selectMode eq 'CANCEL');

    goto QUIT1              if ($selectMode eq 'NONIC');

    if ($selectMode eq 'NOLOAD')
    {
        failed_to_load($newDriver);
        goto ETHERNET_LOCAL;
    }

    goto LOCAL_IP           if ($selectMode eq 'CHANGE');

}

#------------------------------------------------------------
LOCAL_IP:
#------------------------------------------------------------

{
    my $green = $idb->green();
    my $local_ip = '192.168.' . (int(rand(248)) + 2) . '.1';
    if ($green) {
        my %green_props = $green->props;
        $local_ip = $green_props{'ipaddr'} || $local_ip ; 
    }

    ($rc, $choice) = $console->input_page
        (
         title => gettext("Local networking parameters"),
         text  =>
         gettext("Please enter the local IP address for this server.") .
         "\n\n" .
         gettext("If this server is the first machine on your network, we recommend accepting the default value unless you have a specific reason to choose something else.") .
         "\n\n" .
         gettext("If your server is being installed into an existing network, you must choose an address which is not in use by any other computer on this network."),
         value   => $local_ip,
        );

    goto SYSTEM_NAME unless ($rc == 0);

    if ($choice)
    {
        if (isValidIP($choice))
        {
            $choice = cleanIP($choice);
            $idb->set_prop($green->key, 'bootproto', 'static');
            $idb->set_prop($green->key, 'ipaddr', $choice);
            $db->set_value('UnsavedChanges', 'yes');

            goto LOCAL_NETMASK;
        }
    }
    else
    {
        $choice = '';
    }

    ($rc, $choice) = $console->tryagain_page
        (
         title   => gettext("Invalid local IP address"),
         choice  => $choice,
        );
    goto LOCAL_IP;
}

#------------------------------------------------------------
LOCAL_NETMASK:
#------------------------------------------------------------

{
    my $green = $idb->green();
    my $local_netmask = '255.255.255.0';
    my $local_ip = "";
    if ($green) {
        my %green_props = $green->props;
        $local_netmask = $green_props{'netmask'} || '255.255.255.0'; 
        $local_ip = $green_props{'ipaddr'}; 
    }

    ($rc, $choice) = $console->input_page
        (
         title => gettext("Select local subnet mask"),
         text  =>
         gettext("Please enter the local subnet mask for this server.") .
         "\n\n" .
         gettext("If this server is the first machine on your network, we recommend using the default unless you have a specific reason to choose something else.") .
         "\n\n" .
         gettext("If your server is being installed into an existing network, you must choose the same subnet mask used by other computers on this network."),
         value   => $local_netmask
        );

    goto LOCAL_IP unless ($rc == 0);

    if ($choice)
    {
        if ( isValidIP($choice) )
        {
            $choice = cleanIP($choice);
            # Update primary record
            $idb->set_prop($green->key,'netmask', $choice);
            $db->set_value('UnsavedChanges', 'yes');
            goto SERVER_ONLY;
        }
    }
    else
    {
        $choice = '';
    }

    ($rc, $choice) = $console->tryagain_page
        (
         title   => gettext("Invalid local subnet mask"),
         choice  => $choice,
        );

    goto LOCAL_NETMASK;
}

#------------------------------------------------------------
SERVER_ONLY:
#------------------------------------------------------------

{
    $prevScreen = 'LOCAL_NETMASK';
    my $green = $idb->green();
    my %green_props = $green->props;
    my $local_ip = $green_props{'ipaddr'};
    my $local_netmask = $green_props{'netmask'};
    my $gateway_ip = $green_props{'gateway'} || "";


    ($rc, $choice) = $console->input_page
        (
         title => gettext("Select gateway IP address"),
         text  =>
         gettext("In server-only mode, this server will use only one ethernet adapter connected to your local network. If you have a firewall and wish to use this server as your e-mail/web server, you should consult the firewall documentation for networking details.") .
         "\n\n" .
         gettext("Please specify the gateway IP address that this server should use to access the Internet. Leave blank if you have no Internet access."),
         value   => $gateway_ip
        );

    goto LOCAL_NETMASK unless ($rc == 0);

    $choice ||= '';
    if (!$choice)
    {
        $db->delete('GatewayIP');
        goto OTHER_PARAMETERS;
    }

    my $error = undef;
    if (!isValidIP($choice))
    {
	$error = "not a valid IP address";
    }
    elsif (cleanIP($choice) eq $local_ip)
    {
	$error = "address matches local interface address";
    }
    elsif (!ipv4_in_network($local_ip, $local_netmask, "$choice/32"))
    {
	$error = "address is not local";
    }
    if ($error)
    {
	($rc, $choice) = $console->tryagain_page
	    (
	     title   => gettext("Invalid") . " - " . gettext($error),
	     choice  => $choice,
	    );

	goto SERVER_ONLY;
    }
    $idb->set_prop($green->key,'gateway', cleanIP($choice));
    $db->set_value('UnsavedChanges', 'yes');

    goto OTHER_PARAMETERS;
}

#------------------------------------------------------------
OTHER_PARAMETERS:
#------------------------------------------------------------

    1;

#------------------------------------------------------------
DNS_FORWARDER:
#------------------------------------------------------------

{
    my $primary = $db->get_prop('dns', 'NameServers') || '';
    my $secondary = "";
    my @NameServers = ();

    $primary =~ s/,/ /g;


    ($rc, $choice) = $console->input_page
        (
         title => gettext("Corporate DNS server address"),
         text  =>
         gettext("The server is capable of resolving all its own domain hosts and all DHCP assigned ip, if the DHCP server is enabled.") . 
         "\n\n" .
         gettext("You should enter the address of your ISP's DNS servers here to resolve all other domains."),
         value   => $primary
        );

    if ($rc != 0)
    {
        goto $prevScreen;
    }

    $choice =~ s/^ *//;

    if ($choice) {
	my @choices = split(/[, ]+/, $choice);	

	foreach (@choices) {	   
	    if ( isValidIP($_) ) {
		push @NameServers, cleanIP($_);
	    } else {
		($rc, $choice) = $console->tryagain_page
		    (
		     title   => gettext("Invalid IP address for DNS forwarder"),
		     choice  => $choice,
		    );

		goto DNS_FORWARDER;
	    }
	}
    }

    $db->set_prop('dns', 'NameServers', join(',', @NameServers));

    if($self->{bootstrap}) {
	goto SAVE_CONFIG;
    } else { 
	goto QUERY_SAVE_CONFIG;
    }

}

#------------------------------------------------------------
QUERY_SAVE_CONFIG:
#------------------------------------------------------------

{
    if ($db->get_value('UnsavedChanges') eq "no")
    {
        ($rc, $choice) = $console->message_page
            (
             title => gettext("No unsaved changes"),
             text  =>
             gettext("No changes were made during the configuration process") .
             "\n\n" .
             gettext("Press ENTER to proceed."),
             right => gettext("Finish"),
            );

        return;
    }
    else
    {
        ($rc, $choice) = $console->yesno_page
            (
             title => gettext("Activate configuration changes"),
             text  =>
             gettext("Your configuration changes will now be activated. The configuration files on this server will be changed to reflect your new settings. This may take a few minutes.") .
             "\n\n" .
             gettext("Do you wish to activate your changes?"),
            );
    }
    return unless ($rc == 0);

    #------------------------------------------------------------
 SAVE_CONFIG:
    #------------------------------------------------------------

    if ($self->{bootstrap}) {
        event_signal("interface-update");
        $db->set_value('UnsavedChanges', 'no');
	system("/sbin/e-smith/event-queue", "signal");
        goto QUIT1;

    } else {
	$console->infobox(
	    title => gettext("Activating configuration settings"),
	    text => gettext("Please stand by while your configuration settings are activated ..."),
	    );
        event_signal("interface-update");
        $db->set_value('UnsavedChanges', 'no');
        $db->reload;
        $idb->reload;
	my $current_mode = (getppid() == 1) ? "auto" : "login";
	if ($current_mode ne $db->get_value('ConsoleMode'))
	{
	    # If we switch from login to auto or vv, then we
	    # need to quite here
	    goto QUIT1;
	}
	return;
    }
}
#------------------------------------------------------------
QUIT:
#------------------------------------------------------------
{
    if ( $db->get_value('UnsavedChanges') eq 'yes')
    {
	($rc, $choice) = $console->yesno_page
	    (
	     title   => gettext("*** THERE ARE UNACTIVATED CHANGES - QUIT ANYWAY? ***"),
	     text =>
	     gettext("Your configuration changes have been saved but have not yet been activated. This may result in unpredictable system behavior. We recommend that you complete the configuration process and activate the changes before exiting the console.") .
	     "\n\n" .
	     gettext("Are you sure you want to quit with unactivated changes?"),
	    );

	return unless ($rc == 0);
    }
}

QUIT1:

system("/usr/bin/tput", "clear");
exit (0);
}

1;
