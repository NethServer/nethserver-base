#!/usr/bin/perl -w
# vim: ft=perl:

use strict;
use Test::More 'no_plan';

use esmith::util;
use esmith::ConfigDB;

my $db = esmith::ConfigDB->open_ro;

my $internal = $db->get('InternalInterface');
my $external = $db->get('ExternalInterface');
my $assign = $db->get_value('EthernetAssign');

# Test the internal interface.
ok( $assign =~ /^(normal|swapped)$/, "EthernetAssign is set" );
ok( $internal, "InternalInterface exists" );
ok( $internal->prop('Name') eq ($assign eq 'swapped' ? "eth1" : "eth0"),
	"InternalInterface is named correctly" );
ok( $internal->prop('type') eq 'interface', "InternalInterface is an interface" );
ok( $internal->prop('Configuration') eq 'static',
	"InternalInterface Configuration is static" );
ok( $internal->prop('Driver') eq $db->get_value("EthernetDriver1"),
	"InternalInterface Driver is correct" );
ok( $internal->prop('IPAddress') eq $db->get_value("LocalIP"),
	"InternalInterface IPAddress is correct" );
ok( $internal->prop('Netmask') eq $db->get_value("LocalNetmask"),
	"InternalInterface Netmask is correct" );

# There might be an external interface.
SKIP: {
    skip "serveronly mode, no external interface expected", 9
    	if $db->get_value('SystemMode') eq 'serveronly';
    ok( $external, "ExternalInterface exists" );
    ok( $external->prop('type') eq 'interface',
    	"ExternalInterface is an interface" );
    ok( $external->prop('IPAddress') eq $db->get_value('ExternalIP'),
    	"ExternalInterface IPAddress is correct" );
    ok( $external->prop('Netmask') eq $db->get_value('ExternalNetmask'),
    	"ExternalInterface Netmask is correct" );
    ok( $external->prop('Gateway') eq $db->get_value('GatewayIP'),
    	"ExternalInterface Gateway is correct" );
    ok( ($external->prop('Network'), $external->prop('Broadcast')) eq
    	esmith::util::computeNetworkAndBroadcast($external->prop('IPAddress'),
	                                         $external->prop('Netmask')),
		"ExternalInterface Network is correct" );
    if ($db->get_value('AccessType') eq 'dialup')
    {
	ok( $external->prop('Configuration') eq 'dialup',
		"ExternalInterface Configuration is dialup" );
	my $isdn = $db->get_prop('isdn', 'status') || "disabled";
	my $sync_isdn = $db->get_prop('isdn', 'UseSyncPPP') || "no";
	my $name = ($isdn eq "enabled" and $sync_isdn eq "yes") ? 
		"ippp0" : "ppp0";
	ok( $external->prop('Name') eq $name, "ExternalInterface Name is $name" );
    }
    elsif ($db->get_prop('pppoe', 'status') eq 'enabled')
    {
	ok( $external->prop('Configuration') eq 'pppoe',
		"ExternalInterface Configuration is pppoe" );
	ok( $external->prop('Name') eq 'ppp0', "ExternalInterface name is ppp0" );
    }
    elsif ($assign eq 'swapped')
    {
	ok( $external->prop('Driver') eq $db->get_value("EthernetDriver1"),
		"ExternalInterface Driver is correct" );
	ok( $external->prop('Name') eq 'eth0', "ExternalInterface Name is eth0" );
    }
    else
    {
	ok( $external->prop('Driver') eq $db->get_value("EthernetDriver2"),
		"ExternalInterface Driver is correct" );
	ok( $external->prop('Name') eq 'eth1', "ExternalInterface Name is eth1" );
    }

    if ($db->get_value("ExternalDHCP") eq "on")
    {
	if ($db->get_value("DHCPClient") eq "dhi")
	{
	    ok( $external->prop('Configuration') eq "DHCPHostname",
	    	"ExternalInterface Configuration is DHCPHostname" );
	}
	else
	{
	    ok( $external->prop('Configuration') eq "DHCPEthernetAddress",
	    	"ExternalInterface Configuration is DHCPEthernetAddress" );
	}
    }
    else
    {
	unless (($db->get_value('AccessType') eq 'dialup') ||
	        ($db->get_prop('pppoe', 'status') eq 'enabled'))
	{
	    ok( $external->prop('Configuration') eq 'static',
		    "ExternalInterface Configuration is static" );
	}
    }
}

# The interfaces migrate fragment also creates a dhcpcd record.
my $dhcpcd = $db->get("dhcpcd");

ok( defined $dhcpcd, "dhcpcd record exists" );
ok( $dhcpcd->prop('type') eq 'service', "dhcpcd is a service" );
if ($db->get_value("ExternalDHCP") eq "on")
{
    ok( $dhcpcd->prop('status') eq 'enabled', "dhcpcd is enabled" );
}
else
{
    ok( $dhcpcd->prop('status') eq 'disabled', "dhcpcd is disabled" );
}
