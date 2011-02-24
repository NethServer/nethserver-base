package esmith::console::configure;
use strict;
use warnings;
use Locale::gettext;
use esmith::console;
use esmith::util::network qw(:all);
use esmith::db;
use esmith::ethernet;
use Net::IPv4Addr qw(:all);

our @adapters;
our $console;
our $db;

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
	if ($ifName eq "external")
	{
	    # We'll use a VLAN on eth0 for the "dedicated" WAN link
	    $db->set_value("EthernetDriver2", "unknown");
            $db->set_prop("ExternalInterface", "HWAddress", "");
	    return 'CHANGE';
	}
	# Internal, and there's only one
        my (undef, $driver, $hwaddr, undef) = split (/\s+/, $adapters[0], 4);
	$db->set_value("EthernetDriver1", $driver);
        $db->set_prop("InternalInterface", "HWAddress", $hwaddr);
	return 'CHANGE';
    }

    my %tag2driver;
    my %tag2hwaddr;
    my @args;
    my $default;
    my $existing_hwaddr;
    my $skip;

    if ($ifName eq "external")
    {
        $skip = $db->get_prop("InternalInterface", "HWAddress");
        $existing_hwaddr = $db->get_prop("ExternalInterface", "HWAddress");
    }
    else
    {
        $skip = "";
        $existing_hwaddr = $db->get_prop("InternalInterface", "HWAddress");
    }

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

	$tag2driver{$tag} = $driver;
	$tag2hwaddr{$tag} = $hwaddr;

	my $display_name = gettext("Use") . " " . ${driver} . " " .
	    gettext("for chipset") . " " . ${chipset};

	push(@args, $tag, substr($display_name, 0, 65));

        if ($hwaddr ne $skip)
        {
            $default = $tag if $hwaddr eq $existing_hwaddr;
            $default ||= $tag;
        }
    }

    #--------------------------------------------------------
    # These are just to ensure that xgettext knows about the
    # interface types.
    gettext("local");
    gettext("external");
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

    return 'KEEP' if ($tag2hwaddr{$choice} eq $existing_hwaddr);

    $db->set_value($confEntry, $tag2driver{$choice});
    $db->set_prop(
        ($ifName eq "external") ? "ExternalInterface" : "InternalInterface",
        "HWAddress", $tag2hwaddr{$choice}
    );

    return 'CHANGE';
}

sub doit
{
    my $self = shift;
    $console = shift;
    $db = shift;

    return if ($db->get_prop('bootstrap-console', 'ForceSave') eq 'yes'); # We can skip the menus
    my $SystemName = $db->get_value('SystemName');
    my $DomainName = $db->get_value('DomainName');
    my $bootstrapConsole =
	$db->get_prop("bootstrap-console", "Run") || "no";
    my $rebootRequired = "no";
    my ($rc, $choice);

    #------------------------------------------------------------
    CONFIGURE_MAIN:
    #------------------------------------------------------------
    return unless $console->run_screens( "CONFIGURE_MAIN" );

    # Refresh the db
    $db->reload;

    # Run kudzu probe to detect ethernet adapters
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

    goto LOCAL_IP           if ($selectMode eq 'KEEP');
}

#------------------------------------------------------------
LOCAL_IP:
#------------------------------------------------------------

{
    my $local_ip = $db->get_value('LocalIP') || '192.168.' . (int(rand(248)) + 2) . '.1';

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
            $db->set_value('LocalIP', $choice);
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
    ($rc, $choice) = $console->input_page
        (
         title => gettext("Select local subnet mask"),
         text  =>
         gettext("Please enter the local subnet mask for this server.") .
         "\n\n" .
         gettext("If this server is the first machine on your network, we recommend using the default unless you have a specific reason to choose something else.") .
         "\n\n" .
         gettext("If your server is being installed into an existing network, you must choose the same subnet mask used by other computers on this network."),
         value   => $db->get_value('LocalNetmask')
        );

    goto LOCAL_IP unless ($rc == 0);

    if ($choice)
    {
        if ( isValidIP($choice) )
        {
            $choice = cleanIP($choice);
            # Update primary record
            $db->set_value('LocalNetmask', $choice);
            goto SYSTEM_MODE;
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
SYSTEM_MODE:
#------------------------------------------------------------

{
    my $currentmode;
    my $currentnumber;

    if ($db->get_value('SystemMode') eq 'servergateway')
    {
        $currentmode = gettext("Server and gateway");
        $currentnumber = "1.";
    }
    elsif ($db->get_value('SystemMode') eq 'servergateway-private')
    {
        $currentmode = gettext("Private server and gateway");
        $currentnumber = "2.";
    }
    else
    {
        $currentmode = gettext("Server-only");
        $currentnumber = "3.";
    }

    my @args = (
                "1.", gettext("Server and gateway"),
                "2.", gettext("Private server and gateway"),
                "3.", gettext("Server-only"),
               );

    ($rc, $choice) = $console->menu_page
        (
         title   => gettext("Select operation mode"),
         default => $currentnumber,
         text    =>
         gettext("If you want this server to act as a gateway to the Internet, choose one of the server and gateway options. Server and gateway mode acts as a firewall and provides an external web and mail server. Private server and gateway mode also acts as a firewall but disables all incoming services.") .
         "\n\n" .
         gettext("Server-only mode provides services to a local, protected network. If you choose this mode and Internet access is required, the network must be protected by another server configured in server and gateway mode (or another firewall)."),
         argsref => \@args
        );

    goto ETHERNET_LOCAL unless ($rc == 0);

    if ($choice eq "1.")
    {
        $db->set_value('SystemMode', 'servergateway');
        goto SERVER_GATEWAY;
    }

    if ($choice eq "2.")
    {
        $db->set_value('SystemMode', 'servergateway-private');
        goto SERVER_GATEWAY;
    }

    if ($choice eq "3.")
    {
        $db->set_prop("pppoe", "status", "disabled");
        $db->delete("ExternalIP");
        $db->set_value('SystemMode', 'serveronly');
        $db->set_value('AccessType', "dedicated");
        goto SERVER_ONLY;
    }
}

#------------------------------------------------------------
SERVER_GATEWAY:
#------------------------------------------------------------

{
    my $currentmode;
    my $currentnumber;
    my $dialup_support = $db->get_prop("bootstrap-console", "DialupSupport")
			    || "yes";

    if ($dialup_support eq "no")
    {
        $db->set_value('AccessType', 'dedicated');
        goto ETHERNET_EXTERNAL;
    }

    if ($db->get_value('AccessType') eq 'dedicated')
    {
        $currentmode = gettext("Server and gateway - dedicated");
        $currentnumber = "1.";
    }
    else
    {
        $currentmode = gettext("Server and gateway - dialup");
        $currentnumber = "2.";
    }

    my @args = (
                "1.", gettext("Server and gateway - dedicated"),
                "2.", gettext("Server and gateway - dialup"),
               );

    ($rc, $choice) = $console->menu_page
        (
         title => gettext("Select external access mode"),
         default => $currentnumber,
         text =>
         gettext("The next step is to select the access mode that your server will use to connect to the Internet.") .
         "\n\n" .
         gettext("Choose the dedicated option if you access the Internet via a router, a cable modem or ADSL. Choose the dialup option if you use a modem or ISDN connection."),
         argsref => \@args
        );

    goto SYSTEM_MODE unless ($rc == 0);

    if ($choice eq  "1.")
    {
        $db->set_value('AccessType', 'dedicated');
        goto ETHERNET_EXTERNAL;
    }

    if ($choice eq  "2.")
    {
        $db->set_value('AccessType', 'dialup');
        $db->set_prop("pppoe", "status", "disabled");
        goto DIALUP_MODEM;
    }
}

#------------------------------------------------------------
ETHERNET_EXTERNAL:
#------------------------------------------------------------
{
    my $vlan = $db->get_prop('sysconfig', 'VlanWAN');
    if (scalar @adapters == 1 && !$vlan)
    {
        ($rc, $choice) = $console->message_page
            (
             title => gettext("Only one network adapter"),
             text  =>
             gettext("Your system only has a single network adapter. It cannot be used in this configuration."),
	     left => "",
	     right => "Back",
            );
	goto SERVER_GATEWAY;
    }
    my ($selectMode, $newDriver) = ethernetSelect('external', 'EthernetDriver2');

    goto ETHERNET_EXTERNAL  if ($selectMode eq 'CANCEL_MANUAL');

    goto SERVER_GATEWAY     if ($selectMode eq 'CANCEL');

    if ($selectMode eq 'NOLOAD')
    {
        failed_to_load($newDriver);
        goto ETHERNET_EXTERNAL;
    }

    $db->set_value('EthernetAssign', "normal");

    goto SERVER_GATEWAY_DEDICATED;
}

#------------------------------------------------------------
SERVER_GATEWAY_DEDICATED:
#------------------------------------------------------------
{
    unless ($db->get_value('DHCPClient'))
    {
        $db->set_value('DHCPClient', 'dhi');
    }

    my $currentmode;
    my $currentnumber;
    my $shortmode;

    if ($db->get_value('ExternalDHCP') eq 'on')
    {
        if ($db->get_value('DHCPClient') eq 'dhi')
        {
            $currentmode = gettext("use DHCP (send account name as client identifier)");
            $shortmode = gettext("DHCP with account name");
            $currentnumber = "1.";
        }
        else
        {
            $currentmode =
                gettext("use DHCP (send ethernet address as client identifier)");
            $shortmode = gettext("DHCP with ethernet address");
            $currentnumber = "2.";
        }
    }
    elsif ($db->get_prop("pppoe", "status") eq "enabled")
    {
        $currentmode = gettext("use PPP over Ethernet (PPPoE)");
        $shortmode = gettext("PPPoE");
        $currentnumber = "3.";

    }
    else
    {
        $currentmode = gettext("use static IP address (do not use DHCP or PPPoE)");
        $shortmode = gettext("static IP");
        $currentnumber = "4.";
    }

    my @args = (
                "1.", gettext("Use DHCP (send account name as client identifier)"),
                "2.", gettext("Use DHCP (send ethernet address as client identifier)"),
                "3.", gettext("Use PPP over Ethernet (PPPoE)"),
                "4.", gettext("Use static IP address"),
               );

    ($rc, $choice) = $console->menu_page
        (
         title   => gettext("External Interface Configuration"),
	 default => $currentnumber,
         text    =>
         gettext("Next, specify how to configure the external ethernet adapter.") .
         "\n\n" .
         gettext("For cable modem connections, select DHCP. If your ISP has assigned a system name for your connection, use the account name option. Otherwise use the ethernet address option. For residential ADSL, use PPPoE. For most corporate connections, use a static IP address."),
         argsref => \@args
        );

    goto SERVER_GATEWAY unless ($rc == 0);

    if ($choice eq  "3.")
    {
        $db->set_value('ExternalDHCP', 'off');

        $db->set_prop("pppoe", "status", "enabled");
        $db->set_prop("pppoe", "DemandIdleTime", "no");
        $db->set_prop("pppoe", "SynchronousPPP", "no");
        # Delete GatewayIP, as Gateway is via ppp link
        $db->delete('GatewayIP');
        goto PPPoE_ACCOUNT;
    }
    else
    {
        $db->set_prop("pppoe", "status", "disabled");
        if ($choice eq  "1.")
        {
            # Delete GatewayIP, as Gateway is via DHCP
            $db->delete('GatewayIP');
            $db->set_value('ExternalDHCP', 'on');
            $db->set_value('DHCPClient', 'dhi');
            goto DHCP_ACCOUNT;
        }

        if ($choice eq  "2.")
        {
            # Delete GatewayIP, as Gateway is via DHCP
            $db->delete('GatewayIP');
            $db->set_value('ExternalDHCP', 'on');
            $db->set_value('DHCPClient', 'd');
            goto DYNAMIC_DNS_SERVICE;
        }

        if ($choice eq  "4.")
        {
            $db->set_value('ExternalDHCP', 'off');
            $db->set_prop('DynDNS', 'status', 'disabled');
            goto STATIC_IP;
        }
    }
}

#------------------------------------------------------------
DHCP_ACCOUNT:
#------------------------------------------------------------
{
    ($rc, $choice) = $console->input_page
        (
         title => gettext("Enter ISP assigned hostname"),
         text  =>
         gettext("You have selected DHCP (send account name). Please enter the account name assigned by your ISP. You must enter the account name exactly as specified by your ISP."),
         value   => $db->get_value('DialupUserAccount')
        );

    goto SERVER_GATEWAY_DEDICATED unless ($rc == 0);

    $db->set_value('DialupUserAccount', $choice || '');

    goto DYNAMIC_DNS_SERVICE;
}

#------------------------------------------------------------
PPPoE_ACCOUNT:
#------------------------------------------------------------
{
    ($rc, $choice) = $console->input_page
        (
         title => gettext("Select PPPoE user account"),
         text  =>
         gettext("Please enter the user account name for your PPPoE Internet connection. Most PPPoE service providers use an account name and e-mail domain. For example, ") . "fredfrog\@frog.pond",
         value   => $db->get_value('DialupUserAccount')
        );

    goto SERVER_GATEWAY_DEDICATED unless ($rc == 0);

    $db->set_value('DialupUserAccount', $choice || '');

    goto PPPoE_PASSWORD;
}

#------------------------------------------------------------
PPPoE_PASSWORD:
#------------------------------------------------------------

{
    ($rc, $choice) = $console->input_page
        (
         title  => gettext("Select PPPoE password"),
         text   =>
         gettext("Please enter the password for your PPPoE Internet connection."),
         value   => $db->get_value('DialupUserPassword')
        );

    goto PPPoE_ACCOUNT unless ($rc == 0);

    $db->set_value('DialupUserPassword', $choice || '');

    goto DYNAMIC_DNS_SERVICE;
}

#------------------------------------------------------------
DYNAMIC_DNS_SERVICE:
#------------------------------------------------------------
goto OTHER_PARAMETERS unless (-d "/sbin/e-smith/dynamic-dns");

{
    unless (opendir (DIR, "/sbin/e-smith/dynamic-dns"))
    {
        warn gettext("Cannot read directory"),
            " /sbin/e-smith/dynamic-dns", "\n";
        $db->set_prop('DynDNS', 'status', 'disabled');
        goto OTHER_PARAMETERS;

    }
    my @scripts = grep (!/^(\.\.?|custom)$/, readdir (DIR));
    closedir (DIR);

    foreach my $script (@scripts)
    {
        # Grab description from script contents
    }

    my $currentnumber;

    my $status = $db->get_prop('DynDNS', 'status') || "disabled";
    my $service = $db->get_prop('DynDNS', 'Service');
    if ($status eq "disabled")
    {
        $service = "off";
        $currentnumber = "1.";
    }
    else
    {
        if ($service eq 'yi')
        {
            $currentnumber = "2.";
        }

        if ($service eq 'dyndns')
        {
            $currentnumber = "3.";
        }

        if ($service eq 'dyndns.org')
        {
            $currentnumber = "4.";
        }

        if ($service eq 'tzo')
        {
            $currentnumber = "5.";
        }

        if ($service eq 'custom')
        {
            $currentnumber = "6.";
        }
    }

    my @args = (
                "1.", gettext("Do not use a dynamic DNS service"),
                "2.", "www.yi.org"     . " - " . gettext("free service"),
                "3.", "www.dyndns.com" . " - " . gettext("commercial service"),
                "4.", "www.dyndns.org" . " - " . gettext("free service"),
                "5.", "www.tzo.com"    . " - " . gettext("commercial service"),
                "6.", gettext("custom DynDNS service"),
               );

    ($rc, $choice) = $console->menu_page
        (
         title   => gettext("Select dynamic DNS service"),
	 default => $currentnumber,
         text    =>
         gettext("Please specify whether you wish to subscribe to a dynamic DNS service. Such services allow you to have a domain name without a static IP address, and are available from various organizations for free or for a reasonable charge. A notification must be sent to the dynamic DNS service whenever your IP address changes. Your server can automatically do this for some dynamic DNS services.") .
         "\n\n" .
         gettext("Choose which dynamic DNS service you would like to use."),
         argsref => \@args
        );

    goto SERVER_GATEWAY_DEDICATED unless ($rc == 0);

    if ($choice eq  "1.")
    {
        $db->set_prop('DynDNS', 'status', 'disabled');
        goto OTHER_PARAMETERS;
    }
    $db->set_prop('DynDNS', 'status', 'enabled');
    if ($choice eq  "2.")
    {
        $db->set_prop('DynDNS', 'Service', 'yi');
        goto DYNAMIC_DNS_ACCOUNT;
    }

    if ($choice eq  "3.")
    {
        $db->set_prop('DynDNS', 'Service', 'dyndns');
        goto DYNAMIC_DNS_ACCOUNT;
    }

    if ($choice eq  "4.")
    {
        $db->set_prop('DynDNS', 'Service', 'dyndns.org');
        goto DYNAMIC_DNS_ACCOUNT;
    }

    if ($choice eq  "5.")
    {
        $db->set_prop('DynDNS', 'Service', 'tzo');
        goto DYNAMIC_DNS_ACCOUNT;
    }

    if ($choice eq  "6.")
    {
        $db->set_prop('DynDNS', 'Service', 'custom');
        goto DYNAMIC_DNS_ACCOUNT;
    }
}

#------------------------------------------------------------
DYNAMIC_DNS_ACCOUNT:
#------------------------------------------------------------

{
    my $account = $db->get_prop('DynDNS', 'Account') || '';
    my $service = $db->get_prop('DynDNS', 'Service');
    ($rc, $choice) = $console->input_page
        (
         title => gettext("Select dynamic DNS account"),
         text  => gettext("Please enter the account name for your dynamic DNS service"),
         value   => $account
        );

    goto DYNAMIC_DNS_SERVICE unless ($rc == 0);

    if ($choice)
    {
        $db->set_prop('DynDNS', 'Account', $choice);
    }
    else
    {
        $db->set_prop('DynDNS', 'Account', '');
    }

    goto DYNAMIC_DNS_PASSWORD;
}

#------------------------------------------------------------
DYNAMIC_DNS_PASSWORD:
#------------------------------------------------------------

{
    my $account = $db->get_prop('DynDNS', 'Account');
    my $password = $db->get_prop('DynDNS', 'Password') || '';
    ($rc, $choice) = $console->input_page
        (
         title => gettext("Select dynamic DNS password"),
         text  => gettext("Please enter the password for your dynamic DNS service"),
         value   => $password
        );

    goto DYNAMIC_DNS_ACCOUNT unless ($rc == 0);

    if ($choice)
    {
        $db->set_prop('DynDNS', 'Password', $choice);
    }
    else
    {
        $db->set_prop('DynDNS', 'Password', '');
    }

    goto OTHER_PARAMETERS;
}

#------------------------------------------------------------
STATIC_IP:
#------------------------------------------------------------

{
    # Need to do this now, since we delete ExternalIP and
    # the console will throw an uninitialized variable error
    # that you'll never see, but will make rc == 0.

    my $externalIP = $db->get_value('ExternalIP') || "";
    ($rc, $choice) = $console->input_page
        (
         title => gettext("Select static IP address"),
         text  =>
         gettext("You have chosen to configure your external Ethernet connection with a static IP address. Please enter the IP address which should be used for the external interface on this server.") .
         "\n\n" .
         gettext("Please note, this is not the address of your external gateway."),
         value   => $externalIP
        );

    goto SERVER_GATEWAY_DEDICATED unless ($rc == 0);

    if ($choice)
    {
        if (isValidIP($choice) )
        {
            $db->set_value('ExternalIP', cleanIP($choice));
            goto STATIC_NETMASK;
        }
    }
    else
    {
        $choice = '';
    }

    ($rc, $choice) = $console->tryagain_page
        (
         title   => gettext("Invalid external IP address"),
         choice  => $choice,
        );

    goto STATIC_IP;
}

#------------------------------------------------------------
STATIC_NETMASK:
#------------------------------------------------------------

{
    ($rc, $choice) = $console->input_page
        (
         title => gettext("Select subnet mask"),
         text  =>
         gettext("Please enter the subnet mask for your Internet connection. A typical subnet mask is 255.255.255.0."),
         value   => $db->get_value('ExternalNetmask')
        );

    goto STATIC_IP unless ($rc == 0);

    if ($choice)
    {
        if ( isValidIP($choice) )
        {
            # Check for overlapping ranges in external and internal interface IP and netmasks

            # Retrieve the local IP/mask setting
            my $localAddress = $db->get_value('LocalIP');
            my $localNetmask = $db->get_value('LocalNetmask');

            # Retrieve the external IP/mask setting
            my $externalAddress = $db->get_value('ExternalIP');
            my $externalNetmask = cleanIP($choice);

            if ( ipv4_in_network($localAddress, $localNetmask, $externalAddress, $externalNetmask) )
            {

                ($rc, $choice) = $console->message_page
                (
                    title => gettext("Invalid address ranges"),
                    text  => sprintf(gettext(
                                 "Internal address range overlaps external address range" .
                                 "\n\n".
                                 "Local interface: %s/%s" .
                                 "\n" .
                                 "External interface: %s/%s" .
                                 "\n\n".
                                 "Please review your settings."), 
                             $localAddress, $localNetmask, $externalAddress, $externalNetmask
                    )
                );

                goto STATIC_IP;

            }

            $db->set_value('ExternalNetmask', $externalNetmask);
            goto STATIC_GATEWAY;
        }
    }
    else
    {
        $choice = '';
    }

    ($rc, $choice) = $console->tryagain_page
        (
         title   => gettext("Invalid external subnet mask"),
         choice  => $choice,
        );

    goto STATIC_NETMASK;
}

#------------------------------------------------------------
STATIC_GATEWAY:
#------------------------------------------------------------

{
    my $netmaskBits = esmith::util::IPquadToAddr ($db->get_value('ExternalNetmask'));
    my $gateway_ip = $db->get_value('GatewayIP') || "";
    unless ((esmith::util::IPquadToAddr($db->get_value('ExternalIP')) & $netmaskBits) ==
            (esmith::util::IPquadToAddr($db->get_value('GatewayIP')) & $netmaskBits)) {
        $gateway_ip =
            esmith::util::IPaddrToQuad(
                                       (esmith::util::IPquadToAddr($db->get_value('ExternalIP')) & $netmaskBits)
                                       + 1);
    }
    ($rc, $choice) = $console->input_page
        (
         title => gettext("Select gateway IP address"),
         text  =>
         gettext("Please enter the gateway IP address for your Internet connection."),
         value   => $gateway_ip
        );

    goto STATIC_NETMASK unless ($rc == 0);

    $choice ||= '';
    my $error = undef;
    if (!isValidIP($choice))
    {
	$error = "not a valid IP address";
    }
    elsif (cleanIP($choice) eq $db->get_value('ExternalIP'))
    {
	$error = "address matches external interface address";
    }
    elsif (!ipv4_in_network($db->get_value('ExternalIP'),
	$db->get_value('ExternalNetmask'), "$choice/32"))
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

	goto STATIC_GATEWAY;
    }
    $db->set_value('GatewayIP', cleanIP($choice));
    goto OTHER_PARAMETERS;
}

#------------------------------------------------------------
DIALUP_MODEM:
#------------------------------------------------------------

{
    my @args = (
                "COM1", gettext("Set modem port to") . " COM1 (/dev/ttyS0)",
                "COM2", gettext("Set modem port to") . " COM2 (/dev/ttyS1)",
                "COM3", gettext("Set modem port to") . " COM3 (/dev/ttyS2)",
                "COM4", gettext("Set modem port to") . " COM4 (/dev/ttyS3)",
                gettext("ISDN"), gettext("Set modem port to") . " " .
                gettext("internal ISDN card") . " (/dev/ttyI0)",
               );

    ($rc, $choice) = $console->menu_page
        (
         title => gettext("Select modem/ISDN port"),
	 default =>  $db->get_value('DialupModemDevice'),
         text  =>
         gettext("Please specify which serial port your modem or ISDN terminal adapter is connected to. Select ISDN if you wish to use an internal ISDN card."),
         argsref => \@args
        );

    goto SERVER_GATEWAY unless ($rc == 0);

    if ($choice eq  "COM1")
    {
        $db->set_value('DialupModemDevice', '/dev/ttyS0');
    }

    if ($choice eq  "COM2")
    {
        $db->set_value('DialupModemDevice', '/dev/ttyS1');
    }

    if ($choice eq  "COM3")
    {
        $db->set_value('DialupModemDevice', '/dev/ttyS2');
    }

    if ($choice eq  "COM4")
    {
        $db->set_value('DialupModemDevice', '/dev/ttyS3');
    }
    if ($choice eq gettext("ISDN"))
    {
        $db->set_value('DialupModemDevice', '/dev/ttyI0');
    }

    if ($db->get_value('DialupModemDevice') eq '/dev/ttyI0')
    {
        $db->set_prop('ippp', 'status', 'enabled');
        $db->set_prop('isdn', 'status', 'enabled');
        goto HISAX_OPTIONS
    }
    $db->set_prop('ippp', 'status', 'disabled');
    $db->set_prop('isdn', 'status', 'disabled');
    goto MODEM_INIT_STRING;
}

#------------------------------------------------------------
HISAX_OPTIONS:
#------------------------------------------------------------

{
    # See http://ibiblio.org/pub/Linux/distributions/caldera/eServer/\
    # 2.3.1/live/etc/hwprobe.config for a pciid list - we cover most of
    # the cards listed there
    my %isdn_cards = (
                      '1133e001' =>
                      { type => "11",
                        description => "Eicon|DIVA 20PRO" },
                      '1133e002' =>
                      { type => "11",
                        description => "Eicon|DIVA 20" },
                      '1133e003' =>
                      { type => "11",
                        description => "Eicon|DIVA 20PRO_U" },
                      '1133e004' =>
                      { type => "11",
                        description => "Eicon|DIVA 20_U" },
                      '1133e005' =>
                      { type => "11",
                        description => "Eicon|DIVA 2.01 PCI or PCI_LP" },
                      '1133e010' =>
                      { type => "11",
                        description => "Eicon|DIVA Server BRI-2M" },
                      '1133e012' =>
                      { type => "11",
                        description => "Eicon|DIVA Server BRI-8M" },
                      '1133e014' =>
                      { type => "11",
                        description => "Eicon|DIVA Server PRO-30M" },
                      '1133e018' =>
                      { type => "11",
                        description => "Eicon|DIVA Server BRI-2M/-2F" },
                      'e1590002' =>
                      { type => "15",
                        description => "Sedlbauer Speed PCI ISDN" },
                      '10481000' =>
                      { type => "18",
                        description => "Elsa AG|QuickStep 1000" },
                      '10483000' =>
                      { type => "18",
                        description => "Elsa AG|QuickStep 3000" },
                      'e1590001' =>
                      { type => "20",
                        description => "Netjet|Tigerjet 300|320" },
                      '11de6057' =>
                      { type => "21",
                        description => "Teles PCI ISDN network controller" },
                      '11de6120' =>
                      { type => "21",
                        description => "Teles PCI ISDN network controller" },
                      '12671016' =>
                      { type => "24",
                        description => "Dr Neuhaus Niccy PCI" },
                      '12440a00' =>
                      { type => "27",
                        description => "AVM Fritz PCI" },
                      '10b51030' =>
                      { type => "34",
                        description => "Gazel/PLX R685" },
                      '10b51151' =>
                      { type => "34",
                        description => "Gazel/PLX DJINN_ITOO" },
                      '10b51152' =>
                      { type => "34",
                        description => "Gazel/PLX R753" },
                      '13972bd0' =>
                      { type => "35",
                        description => "ISDN network controller [HFC-PCI]" },
                      '1397b000' =>
                      { type => "35",
                        description => "ISDN network controller [HFC-PCI]" },
                      '1397b006' =>
                      { type => "35",
                        description => "ISDN network controller [HFC-PCI]" },
                      '1397b007' =>
                      { type => "35",
                        description => "ISDN network controller [HFC-PCI]" },
                      '1397b008' =>
                      { type => "35",
                        description => "ISDN network controller [HFC-PCI]" },
                      '1397b009' =>
                      { type => "35",
                        description => "ISDN network controller [HFC-PCI]" },
                      '1397b00a' =>
                      { type => "35",
                        description => "ISDN network controller [HFC-PCI]" },
                      '1397b00b' =>
                      { type => "35",
                        description => "ISDN network controller [HFC-PCI]" },
                      '1397b00c' =>
                      { type => "35",
                        description => "ISDN network controller [HFC-PCI]" },
                      '1397b100' =>
                      { type => "35",
                        description => "ISDN network controller [HFC-PCI]" },
                      '15b02bd0' =>
                      { type => "35",
                        description => "Zoltrix ISDN network controller [HFC-PCI]" },
                      '10430675' =>
                      { type => "35",
                        description => "Asuscom ISDNLINK 128K [HFC-PCI]" },
                      '06751700' =>
                      { type => "36",
                        description => "Dynalink IS64PH ISDN network controller" },
                      '06751702' =>
                      { type => "36",
                        description => "Dynalink IS64PH ISDN network controller" },
                      '06751704' =>
                      { type => "36",
                        description => "Dynalink IS64PH ISDN network controller" },
                      '10506692' =>
                      { type => "36",
                        description => "Winbond 6692 ISDN network controller" },
                      '15ad0710' =>
                      { type => "FF",
                        description =>
                        "Test thingy to check detection (actually VMWare display)" },
                     );

    my $card;
    open (PCI, "/proc/bus/pci/devices");
    while (my $pci_data = <PCI>)
    {
        my $id = (split(/\s+/, $pci_data))[1];
        $card = $isdn_cards{$id};
        last if defined $card;
    }
    close (PCI);
    if (defined $card)
    {
        my $description = $$card{'description'};
        ($rc, $choice) = $console->yesno_page
            (
             title => gettext("ISDN card detected"),
             text  =>
             gettext("Do you wish to use the following ISDN card for your Internet connection?") .
             "\n\n" .
             $description,
            );

        if ($rc == 0)
        {
            my $type = $$card{'type'};
            $db->set_prop('isdn', 'Type', "$type");
            goto DIALUP_ACCESS_NUMBER;
        }
    }

    my $hisax_options = $db->get_prop('isdn', 'HisaxOptions') || "";
    ($rc, $choice) = $console->input_page
        (
         title => gettext("ISDN driver options"),
         text  =>
         gettext("You have selected an internal ISDN card.") .
         "\n\n" .
         gettext("The ISDN software will need to be told what ISDN hardware you have. It may also need to be told what protocol number to use and may need to be given some additional information about your hardware such as the I/O address and interrupt settings.") .
         "\n\n" .
         gettext("This information is provided via an options string. An example is") .
         " " .  qq("type=27 protocol=2") . " " .
         gettext("which would be used to set the") .
         " " . qq("AVM Fritz!PCI") . " " .
         gettext("to EURO-ISDN."),
         value   => $hisax_options
        );

    goto DIALUP_MODEM unless ($rc == 0);

    if ($choice)
    {
        $db->set_prop('isdn', 'HisaxOptions', $choice);
    }
    else
    {
        $db->delete_prop('isdn', 'HisaxOptions');
    }
}

#------------------------------------------------------------
ISDN_MSN:
#------------------------------------------------------------
goto MODEM_INIT_STRING;         # Skip this page - only for dial-in

{
    my $msn = $db->get_prop('isdn', 'Msn');
    $msn = "" unless (defined $msn);
    ($rc, $choice) = $console->input_page
        (
         title => gettext("Multiple Subscriber Numbering"),
         text  =>
         gettext("Your ISDN line may have more than one number associated with it known as Multiple Subscriber Numbering (MSN). In order to receive an incoming ISDN call from an ISP or a remote site, you may need to configure your ISDN card with its MSN so that ISDN calls are routed correctly. If you do not know this number, you can leave this value blank."),
         value   => $msn
        );

    goto HISAX_OPTIONS unless ($rc == 0);

    unless ($choice eq "" or $choice =~ /^[-,0-9]+$/)
    {
        ($rc, $choice) = $console->tryagain_page
            (
             title   => gettext("Invalid Multiple Subscriber Numbering (MSN)"),
             choice  => $choice,
            );

        goto ISDN_MSN;
    }
    $db->set_prop('isdn', 'Msn', "$choice");
    goto DIALUP_ACCESS_NUMBER;
}
#------------------------------------------------------------
MODEM_INIT_STRING:
#------------------------------------------------------------

{
    my $modem_init = $db->get_value('ModemInit') || "";
    my $modem = $db->get_value('DialupModemDevice') || "";

    my $isdn_msg =
        gettext("You have selected an internal ISDN card.") .
            "\n\n" .
        gettext("The driver for this card includes modem emulation software, and modem control commands are used by the networking software to configure and control the ISDN interface card.") .
                    "\n\n" .
        gettext("The precise behavior of your ISDN card can be modified by using a specific modem initialization string, to adjust the settings of the card, or to modify its default behavior. Most cards should work correctly with the default settings, but you may enter a modem initialization string here if required.");

    my $modem_msg =
        gettext("You have selected a modem device.") .
            "\n\n" .
        gettext("The precise behavior of your modem can be modified by using a specific modem initialization string, to adjust the settings of your modem, or to modify its default behavior. You may enter a modem initialization string here.") .
                    "\n\n" .
        gettext("Many modems will work correctly without any special settings. If you leave this field blank, the default string of") .
                            " " . qw("L0M0") . " " .
        gettext("will be used. This turns the modem speaker off, so that you will not be bothered by the noises that a modem makes when it starts a connection.");

    my $msg = ($modem eq '/dev/ttyI0') ? $isdn_msg : $modem_msg;

    ($rc, $choice) = $console->input_page
        (
         title   => gettext("Modem initialization string"),
         text    => $msg,
         value   => $modem_init
        );

    unless ($rc == 0)
    {
        if ($db->get_value('DialupModemDevice') eq '/dev/ttyI0')
        {
            goto HISAX_OPTIONS;
        }
        else
        {
            goto DIALUP_MODEM;
        }
    }

    if ($choice)
    {
        $db->set_value('ModemInit', $choice);
    }
    else
    {
        $db->delete('ModemInit');
    }
    goto DIALUP_ACCESS_NUMBER;
}

#------------------------------------------------------------
DIALUP_ACCESS_NUMBER:
#------------------------------------------------------------

{
    my $title = gettext("Select access phone number");

    my $msg =
        gettext("Please enter the access phone number for your Internet connection. Long distance numbers can be entered. The phone number must not contain spaces, but may contain dashes for readability. Commas may be inserted where a delay is required. For example, if you need to dial 9 first, then wait, then dial a phone number, you could enter") . " "
            . qq("9,,,123-4567");

    ($rc, $choice) = $console->input_page
        (
         title   => $title,
         text    => $msg,
         value   => $db->get_value('DialupPhoneNumber')
        );

    goto MODEM_INIT_STRING unless ($rc == 0);

    if ($choice)
    {
        if ($choice =~ /^[-,0-9]+$/)
        {
            $db->set_value('DialupPhoneNumber', "$choice");
            goto DIALUP_ACCOUNT;
        }
    }
    else
    {
        $choice = '';
    }

    ($rc, $choice) = $console->tryagain_page
        (
         title   => gettext("Invalid access phone number"),
         choice  => $choice,
        );

    goto DIALUP_ACCESS_NUMBER;
}

#------------------------------------------------------------
DIALUP_ACCOUNT:
#------------------------------------------------------------

{
    my $msg = gettext("Please enter the user account name for your Internet connection.")
        . "\n\n" .
            gettext("Please note that account names are usually case sensitive.");

    ($rc, $choice) = $console->input_page
        (
         title   => gettext("Select dialup user account"),
         text    => $msg,
         value   => $db->get_value('DialupUserAccount')
        );

    goto DIALUP_ACCESS_NUMBER unless ($rc == 0);

    $db->set_value('DialupUserAccount', $choice || '');
    goto DIALUP_PASSWORD;
}

#------------------------------------------------------------
DIALUP_PASSWORD:
#------------------------------------------------------------

{
    my $msg = gettext("Please enter the password for your Internet connection.")
        . "\n\n" .
            gettext("Please note that passwords are usually case sensitive.");

    ($rc, $choice) = $console->input_page
        (
         title   => gettext("Select dialup password"),
         text    => $msg,
         value   => $db->get_value('DialupUserPassword')
        );

    goto DIALUP_ACCOUNT unless ($rc == 0);

    $db->set_value('DialupUserPassword', $choice || '');
    goto INITIALIZE_CONNECT_TIMES;
}

#------------------------------------------------------------
INITIALIZE_CONNECT_TIMES:
#------------------------------------------------------------
my %policy2string =
    (
     "never"        => "No connection",
     "short"        => "Short connect times to minimize minutes off-hook",
     "medium"       => "Medium connect times",
     "long"     => "Long connect times to minimize dialing delays",
     "continuous"   => "Continuous connection",
    );

my @connect_options;
my %gettext2policy;

goto DIALUP_OFFICE if scalar @connect_options;

foreach (keys %policy2string)
{
    push @connect_options, gettext($_), gettext($policy2string{$_});
    $gettext2policy{gettext($_)} = $_;
}

#------------------------------------------------------------
DIALUP_OFFICE:
#------------------------------------------------------------
{
    my $val = $db->get_value('DialupConnOffice') || 'medium';

    ($rc, $choice) = $console->menu_page
        (
         title => gettext("Select connect policy"),
	 default => gettext($val),
         text  =>
         gettext("Select the dialup connect policy that you would like to use during office hours (8:00 AM to 6:00 PM) on weekdays."),

         argsref => \@connect_options,
        );

    goto DIALUP_PASSWORD unless ($rc == 0);

    $db->set_value('DialupConnOffice', $gettext2policy{$choice});

    goto DIALUP_OUTSIDE;
}

#------------------------------------------------------------
DIALUP_OUTSIDE:
#------------------------------------------------------------

{
    my $val = $db->get_value('DialupConnOutside') || 'medium';

    ($rc, $choice) = $console->menu_page
        (
         title => gettext("Select connect policy"),
	 default => gettext($val),
         text  =>
         gettext("Please select the dialup connect policy that you would like to use outside office hours (6:00 PM to 8:00 AM) on weekdays."),
         argsref => \@connect_options,
        );

    goto DIALUP_OFFICE unless ($rc == 0);

    $db->set_value('DialupConnOutside', $gettext2policy{$choice});

    goto DIALUP_WEEKEND;
}

#------------------------------------------------------------
DIALUP_WEEKEND:
#------------------------------------------------------------

{
    my $val = $db->get_value('DialupConnWeekend') || 'medium';
    ($rc, $choice) = $console->menu_page
        (
         title => gettext("Select connect policy"),
	 default => gettext($val),
         text  =>
         gettext("Please select the dialup connect policy that you would like to use during the weekend."),
         argsref => \@connect_options,
        );

    goto DIALUP_OUTSIDE unless ($rc == 0);

    $db->set_value('DialupConnWeekend', $gettext2policy{$choice});

    goto DYNAMIC_DNS_SERVICE;
}


#------------------------------------------------------------
SERVER_ONLY:
#------------------------------------------------------------

{
    if (scalar @adapters == 2)
    {
        my (undef, $driver1, undef, undef) = split (/\s+/, $adapters[0], 4);
        my (undef, $driver2, undef, undef) = split (/\s+/, $adapters[1], 4);
        if($driver1 eq $driver2)
        {
	    my $val = $db->get_prop('InternalInterface', 'NICBonding') || 'disabled';
            my @args = (
                    gettext("enabled"), gettext("Enable NIC bonding"),
                    gettext("disabled"), gettext("Disable NIC bonding")
                );

            ($rc, $choice) = $console->menu_page
                (
                title => gettext("NIC Bonding"),
		default => gettext($val),
                text  =>
                gettext("You have more than one network adapter. Would you like to bond them together into a single interface? This can provide greater throughput and/or failure resiliency, depending on your adapters and network configuration."),
                argsref => \@args
            );

	    $db->set_prop('InternalInterface', 'NICBonding', 
                    ($choice eq gettext('enabled')) ? 'enabled' : 'disabled');

            $db->set_value("EthernetDriver2", 
                ($db->get_prop('InternalInterface', 'NICBonding', 
                    'enabled'))
                ? $db->get_value("EthernetDriver1") : 'unknown');
        }

        # SME 935 - edit NIC bonding option string
        if($db->get_prop('InternalInterface', 'NICBonding') eq 'enabled')
        {
            my $msg = gettext("The NIC bonding driver allows various modes and performance options. Edit the option string below if the defaults are not suitable.\n\nMost users do not need to change this setting.\n");
            my $bond_opts = $db->get_prop("InternalInterface", "NICBondingOptions") || '';
            ($rc, $choice) = $console->input_page
                (
                 title   => gettext("NIC Bonding Options"),
                 text    => $msg,
                 value   => $bond_opts
                );

            goto SERVER_ONLY unless ($rc == 0);

            $db->set_prop('InternalInterface', 'NICBondingOptions', 
                $choice);
        }
    }
    
    goto OTHER_PARAMETERS unless ($db->get_value('AccessType') eq 'dedicated');

    my $gateway_ip = $db->get_value('GatewayIP') || "";
    my $netmaskBits = esmith::util::IPquadToAddr ($db->get_value('LocalNetmask'));
    unless ((esmith::util::IPquadToAddr($db->get_value('LocalIP')) & $netmaskBits) ==
            (esmith::util::IPquadToAddr($db->get_value('GatewayIP')) & $netmaskBits)) {
        $gateway_ip =
            esmith::util::IPaddrToQuad(
                                       (esmith::util::IPquadToAddr($db->get_value('LocalIP')) & $netmaskBits)
                                       + 1);
    }

    ($rc, $choice) = $console->input_page
        (
         title => gettext("Select gateway IP address"),
         text  =>
         gettext("In server-only mode, this server will use only one ethernet adapter connected to your local network. If you have a firewall and wish to use this server as your e-mail/web server, you should consult the firewall documentation for networking details.") .
         "\n\n" .
         gettext("Please specify the gateway IP address that this server should use to access the Internet. Leave blank if you have no Internet access."),
         value   => $gateway_ip
        );

    goto SYSTEM_MODE unless ($rc == 0);

    $choice ||= '';
    if (!$choice)
    {
        $db->delete('GatewayIP');
        $db->set_value('AccessType', 'off');
        goto OTHER_PARAMETERS;
    }

    my $error = undef;
    if (!isValidIP($choice))
    {
	$error = "not a valid IP address";
    }
    elsif (cleanIP($choice) eq $db->get_value('LocalIP'))
    {
	$error = "address matches local interface address";
    }
    elsif (!ipv4_in_network($db->get_value('LocalIP'),
	$db->get_value('LocalNetmask'), "$choice/32"))
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
    $db->set_value('GatewayIP', cleanIP($choice));
    $db->set_value('AccessType', 'dedicated');
    goto OTHER_PARAMETERS;
}

#------------------------------------------------------------
OTHER_PARAMETERS:
#------------------------------------------------------------
# Sample UnsavedChanges at this point - nothing after here
# should require a reboot - and we don't require a reboot first time
# through
#------------------------------------------------------------
if ($bootstrapConsole eq "no")
{
    $rebootRequired = $db->get_value('UnsavedChanges');
}
#------------------------------------------------------------

DHCP_SERVER:
{
    my $start = $db->get_prop("dhcpd", "start") || '0.0.0.65';
    my $end = $db->get_prop("dhcpd", "end") || '0.0.0.250';
    my $priv_ip = $db->get_value('LocalIP');
    my $priv_mask = $db->get_value('LocalNetmask');
    my $priv_net = ipv4_network($priv_ip, $priv_mask);
    my $localip = esmith::util::IPquadToAddr($priv_ip);
    my $netmask = esmith::util::IPquadToAddr($priv_mask);
    $start      = esmith::util::IPquadToAddr($start);
    $end        = esmith::util::IPquadToAddr($end);
    # AND-out the network bits, and OR that with our current dhcp values.
    my $localnet = $localip & $netmask;
    # Delete the current DHCP leases file if we are changing networks
    unless ((($start & $netmask) == $localnet) &&
	    (($end & $netmask) == $localnet))
    {
	my $dhcpLeases = "/var/lib/dhcp/dhcpd.leases";
	open (WR, ">$dhcpLeases")
	    or die gettext("Can't open output file"),
		" $dhcpLeases", ": $!\n";
	close WR;
    }
    # AND-out the host bits from the start and end ips.
    # And, OR our local network with our start and end host values.
    $start = $localnet | ($start & ~$netmask);
    $end = $localnet | ($end & ~$netmask);
    # Make sure that $start is less than $end (might not be if netmask has changed
    if ($start > $end)
    {
	my $temp = $start;
	$start = $end;
	$end = $temp;
    }
    $start = esmith::util::IPaddrToQuad($start);
    $end   = esmith::util::IPaddrToQuad($end);
    # That's it. Set them back. These will hopefully be reasonable defaults.
    $db->set_prop("dhcpd", "start", $start);
    $db->set_prop("dhcpd", "end", $end);
    my $DHCPServer = ($db->get_prop('dhcpd', 'status') eq 'enabled') ?
        gettext("On") : gettext("Off");

    my @args =
        (
         gettext("On"),  gettext("Provide DHCP service to local network"),
         gettext("Off"), gettext("Do not provide DHCP service to local network"),
        );

    ($rc, $choice) = $console->menu_page
        (
         title => gettext("Select DHCP server configuration"),
	 default => $DHCPServer,
         text  =>
         gettext("Please specify whether you would like this server to provide DHCP service to your local network. This will let you assign IP addresses to your other network computers automatically by configuring them to obtain their IP information using DHCP.") .
         "\n\n" .
         gettext("We strongly advise that all clients are configured using DHCP."),
         argsref => \@args
        );

    goto SYSTEM_MODE unless ($rc == 0);

    if ($choice eq gettext("On")) {
        $db->set_prop('dhcpd', 'status', 'enabled');
        goto DHCP_SERVER_BEGIN;
    }

    if ($choice eq gettext("Off"))
    {
        $db->set_prop('dhcpd', 'status', 'disabled');
        goto DNS_FORWARDER;
    }

    goto DNS_FORWARDER;
}

#------------------------------------------------------------
DHCP_SERVER_BEGIN:
#------------------------------------------------------------

{
    my $start = $db->get_prop("dhcpd", "start") || '0.0.0.65';
    my $priv_ip = $db->get_value('LocalIP');
    my $priv_mask = $db->get_value('LocalNetmask');
    my $priv_net = ipv4_network($priv_ip, $priv_mask);

    my $errmsg = "";

    ($rc, $choice) = $console->input_page
        (
         title => gettext("Select beginning of DHCP host number range"),
         text  =>
         gettext("You must reserve a range of host numbers for the DHCP server to use.") .
         "\n\n" .
         gettext("Please enter the first host number in this range. If you are using the standard server defaults and have no particular preference, you should keep the default values."),
         value   => $start
        );

    goto DHCP_SERVER unless ($rc == 0);

    if ($choice)
    {
        if ( isValidIP($choice) )
        {
	    my $dhcp_net = ipv4_network($choice, $priv_mask);
	    if ($dhcp_net eq $priv_net)
	    {
		# need to check for valid range as well.
		unless ($choice eq $start)
		{
		    $db->set_prop('dhcpd', 'start', cleanIP($choice));
		}
		goto DHCP_SERVER_END;
	    }
	    else
	    {   
		$errmsg = gettext("That address is not on the local network.");
	    }
	}
	else
	{   
	    $errmsg = gettext("Invalid IP address for DHCP start");
	}
    }
    else
    {
        $choice = '';
	$errmsg = gettext("You must provide an IP address for the start of the DHCP range.");
    }

    ($rc, $choice) = $console->tryagain_page
        (
         title   => $errmsg,
         choice  => $choice,
        );

    goto DHCP_SERVER_BEGIN;
}

#------------------------------------------------------------
DHCP_SERVER_END:
#------------------------------------------------------------

{
    my $serverStart = $db->get_prop('dhcpd', 'start');
    my $serverEnd   = $db->get_prop('dhcpd', 'end');
    my $priv_ip = $db->get_value('LocalIP');
    my $priv_mask = $db->get_value('LocalNetmask');
    my $priv_net = ipv4_network($priv_ip, $priv_mask);
    my $errmsg = "";

    ($rc, $choice) = $console->input_page
        (
         title => gettext("Select end of DHCP host number range"),
         text  =>
         gettext("Please enter the last host address in this range. If you are using the standard server defaults and have no particular preference, you should keep the default value."),
         value   => $serverEnd
        );

    goto DHCP_SERVER_BEGIN unless ($rc == 0);

    if ($choice)
    {
        if ( isValidIP($choice) )
        {
	    my $dhcp_net = ipv4_network($choice, $priv_mask);
	    if ($dhcp_net eq $priv_net)
	    {
		# There are a few additional things to confirm here. We now
		# know that the chosen range is on the same network as the
		# private interface. We should ensure that it does not overlap
		# the private interface, and that the end is larger than the
		# beginning. 
		if (cmpIP($serverStart, $choice) < 0)
		{
		    if ((cmpIP($priv_ip, $serverStart) < 0) ||
			(cmpIP($choice, $priv_ip) < 0))
		    {
			# need to check for valid range as well.
			unless ($choice eq $serverEnd)
			{
			    $db->set_prop('dhcpd', 'end', cleanIP($choice));
			}
			goto DNS_FORWARDER;
		    }
		    else
		    {
			$errmsg = gettext("The IP range cannot include our private network address.");
			$choice = $priv_ip;
		    }
		}
		else
		{
		    $errmsg = gettext("The end of the range must be larger than the start.");
		    $choice = $serverStart;
		}
	    }
	    else
	    {
		$errmsg = gettext("That address is not on the local network.");
	    }
        }
	else
	{
	    $errmsg = gettext("Invalid IP address for DHCP start");
	}
    }
    else
    {
        $choice = '';
	$errmsg = gettext("You must provide an IP address for the end of the DHCP range.");
    }

    ($rc, $choice) = $console->tryagain_page
        (
         title   => $errmsg,
         choice  => $choice,
        );

    goto DHCP_SERVER_END;
}

#------------------------------------------------------------
DNS_FORWARDER:
#------------------------------------------------------------

{
    my $primary = $db->get_prop('dnscache', 'Forwarder') || '';
    ($rc, $choice) = $console->input_page
        (
         title => gettext("Corporate DNS server address"),
         text  =>
         gettext("If this server does not have access to the Internet, or you have special requirements for DNS resolution, enter the DNS server IP address here.") . 
         "\n\n" .
         gettext("This field should be left blank unless you have a specific reason to configure another DNS server.") .
         "\n\n" .
         gettext("You should not enter the address of your ISP's DNS servers here, as the server is capable of resolving all Internet DNS names without this additional configuration."),
         value   => $primary
        );

    if ($rc != 0)
    {
        goto DHCP_SERVER;
    }

    if ($choice)
    {
        if ( isValidIP($choice) )
        {
            $db->set_prop('dnscache', 'Forwarder', cleanIP($choice));
            goto QUERY_SAVE_CONFIG;
        }
        elsif ($choice =~ /^\s*$/)
        {
            $db->delete_prop('dnscache', 'Forwarder');
            goto QUERY_SAVE_CONFIG;
        }
    }
    else
    {
        $db->delete_prop('dnscache', 'Forwarder');
        goto QUERY_SAVE_CONFIG;
    }

    ($rc, $choice) = $console->tryagain_page
        (
         title   => gettext("Invalid IP address for DNS forwarder"),
         choice  => $choice,
        );

    goto DNS_FORWARDER;
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
        if ($rebootRequired eq "yes")
        {
            $db->set_prop("bootstrap-console", "Run", "yes");
            $db->set_prop("bootstrap-console", "ForceSave", "yes");
	    ($rc, $choice) = $console->yesno_page
		(
		 title   => gettext("Changes will take effect after reboot"),
		     text =>
			 gettext("The new configuration will take effect when you reboot the server.") .
			 "\n\n" .
			 gettext("Do you wish to reboot right now?"),
		);

	    return unless ($rc == 0);

	    system("/usr/bin/tput", "clear");
	    system("/sbin/e-smith/signal-event", "reboot");

	    # A bit of a hack to avoid the console restarting before the
	    # reboot takes effect.

	    sleep(600);
        }
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
    # After saving config we don't need to run it again on the next reboot.
    $db->set_prop("bootstrap-console", "ForceSave", "no");
    $db->set_prop("bootstrap-console", "Run", "no");

    $console->infobox(
           title => gettext("Activating configuration settings"),
           text => gettext("Please stand by while your configuration settings are activated ..."),
          );

    if ($bootstrapConsole eq "yes")
    {
        system("/sbin/e-smith/signal-event", "bootstrap-console-save");
        goto QUIT1;
    }
    else
    {
        system("/sbin/e-smith/signal-event", "console-save");
        $db->reload;

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
    if ( $db->get_value('UnsavedChanges') eq 'yes' )
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
