#
# $Id: review.pm,v 1.16 2003/06/02 20:50:49 charlieb Exp $
#

package    esmith::FormMagick::Panel::review;

use strict;
use esmith::DomainsDB;
use esmith::ConfigDB;
use esmith::NetworksDB;
use esmith::FormMagick;
use esmith::util;
use File::Basename;
use Exporter;
use Carp;

our @ISA = qw(esmith::FormMagick Exporter);

our @EXPORT = qw( print_row print_page print_header gen_email_addresses get_local_domain
		  gen_domains  get_local_networks  print_serveronly_stanza 
		  print_gateway_stanza print_dhcp_stanza
		  get_value get_prop get_net_prop

);



our $VERSION = sprintf '%d.%03d', q$Revision: 1.16 $ =~ /: (\d+).(\d+)/;

our $db = esmith::ConfigDB->open || die "Couldn't open config db";
our $domains = esmith::DomainsDB->open || die "Couldn't open domains";
our $networks = esmith::NetworksDB->open || die "Couldn't open networks";


=pod 

=head1 NAME

esmith::FormMagick::Panels::review - useful panel functions

=head1 SYNOPSIS

    use esmith::FormMagick::Panels::review;

    my $panel = esmith::FormMagick::Panel::review->new();
    $panel->display();

=head1 DESCRIPTION

=cut

# {{{ new

=head2 new();

Exactly as for esmith::FormMagick

=begin testing

$ENV{ESMITH_CONFIG_DB} = "10e-smith-base/configuration.conf";
$ENV{ESMITH_NETWORKS_DB} = "10e-smith-base/networks.conf";
$ENV{ESMITH_DOMAINS_DB} = "10e-smith-base/domains.conf";

use_ok('esmith::FormMagick::Panel::review');
use vars qw($panel);
ok($panel = esmith::FormMagick::Panel::review->new(), "Create panel object");
isa_ok($panel, 'esmith::FormMagick::Panel::review');

=end testing

=cut



sub new {
    shift;
    my $self = esmith::FormMagick->new();
    $self->{calling_package} = (caller)[0];
    bless $self;
    return $self;
}

# }}}

=head2 get_prop ITEM PROP

A simple accessor for esmith::ConfigDB::Record::prop

=cut

sub get_prop {
  my $fm = shift if (ref($_[0]) ); # If we're being called in a formmagick context
				 # The first argument will always be a fm.
				 #otherwise, we don't want to grab it
  my $item = shift;
  my $prop = shift;

  my $record = $db->get($item);
  if ($record) {
    return $record->prop($prop);
  }
  else {
    return '';
  }

}

=head2 get_net_prop ITEM PROP $fm $item $prop

A simple accessor for esmith::NetworksDB::Record::prop

=cut

sub get_net_prop {
  my $fm = shift;
  my $item = shift;
  my $prop = shift;

  my $record = $networks->get($item);
  if ($record) {
    return $record->prop($prop);
  }
  else {
    return '';
  }

}

=head2 get_value ITEM

A simple accessor for esmith::ConfigDB::Record::value

=cut

sub get_value {
  my $fm = shift;
  my $item = shift;
  my $record = $db->get($item);
  if ($record) {
    return $record->value();
  }
  else {
    return '';
  }
  
}



=head2 print_header FORMMAGICK HEADER

   Prints an arbitrary "header" (h2) in the context of the form

=cut


sub print_header {
    my ($fm, $word) = @_;
    my $q = $fm->{cgi};
#    print $q->Tr(esmith::cgi::genDoubleCell($q, $q->h3($fm->localise($word)),"normal"));
    $word = $fm->localise($word);
    print qq(<tr><td colspan=2><h3>$word</h3></td></tr>),"\n";
    return undef;

}


=head2 print_row  FORMMAGICK LABEL VALUE

   Prints a row <tr><td>LABEL</td><td>VALUE</td></tr> in the context of the form.
  LABEL is localized. VALUE is not.

=cut


sub print_row {
    my $self = shift;
    my ($label, $value) = @_;
    $label = $self->localise($label);
    print qq(<tr><td class="sme-noborders-label">$label</td><td class="sme-noborders-content">$value</td></tr>),"\n";
    return undef;
}

=head2 print_gateway_stanza

If this system is a server gateway, show the external ip and gateway ip

=cut

sub print_gateway_stanza
{
    my $fm = shift;
    if (get_value($fm,'SystemMode') =~ /servergateway/)
    {
	my $ip = get_value($fm,'ExternalIP');
	my $static =
	     (get_value($fm, 'AccessType') eq 'dedicated') &&
	     (get_value($fm, 'ExternalDHCP') eq 'off') &&
	     (get_prop($fm, 'pppoe', 'status') eq 'disabled');
	if ($static)
	{
	    $ip .= "/".get_value($fm,'ExternalNetmask');
	}
	print_row($fm, 'EXTERNAL_IP_ADDRESS_SUBNET_MASK', $ip);
	if ($static)
	{
	    print_row($fm, 'GATEWAY', get_value($fm,'GatewayIP') );
	}
    }
}
=head2 print_serveronly_stanza

If this system is a standalone server with net access, show the external
gateway IP

=cut

sub print_serveronly_stanza {
  my $fm = shift;
  if ( (get_value($fm,'SystemMode') eq 'serveronly') &&
       get_value($fm,'AccessType') && 
       (get_value($fm,'AccessType') ne "off")) {
    print_row($fm, 'GATEWAY', get_value($fm,'GatewayIP') );
  }
  
}

=head2 print_dhcp_stanza 

Prints out the current state of dhcp service


=cut

sub print_dhcp_stanza {
    my $fm = shift;
    print_row($fm,'DHCP_SERVER', (get_prop($fm,'dhcpd','status') || 'disabled' ));

    if (get_prop($fm,'dhcpd', 'status') eq 'enabled') {
        print_row($fm, 'BEGINNING_OF_DHCP_ADDRESS_RANGE',
				get_prop($fm,'dhcpd','start') || '' );
        print_row($fm,'END_OF_DHCP_ADDRESS_RANGE',
				get_prop($fm,'dhcpd','end') || '' );
    }
}

=head2 gen_domains 

    Returns a string of the domains this SME Server serves or a localized string
    saying "no domains defined"

=cut

sub gen_domains {
    my $fm = shift;

    my @virtual = $domains->get_all_by_prop( type => 'domain');
    my $numvirtual = @virtual;
    if ($numvirtual == 0) {
        $fm->localise("NO_VIRTUAL_DOMAINS");
    }
    else {
                my $out = "";
        my $domain;
        foreach $domain (sort @virtual) {
            if ($out ne "") {
                $out .= "<BR>";
            }
            $out .= $domain->key;
        }
        return $out;
    }
}

=head2 gen_email_addresses

    Returns a string of the various forms of email addresses that work 
    on an SMEServer

=cut

sub gen_email_addresses {
    my $fm = shift;

    my $domain = get_value($fm,'DomainName'); 
    my $useraccount = $fm->localise("EMAIL_USERACCOUNT");
    my $firstname = $fm->localise("EMAIL_FIRSTNAME");
    my $lastname = $fm->localise("EMAIL_LASTNAME");

        my $out = "<I>" . $useraccount . "</I>\@" . $domain . "<BR>"
        . "<I>" . $firstname . "</I>.<I>" . $lastname . "</I>\@" . $domain . "<BR>"
        . "<I>" . $firstname . "</I>_<I>" . $lastname . "</I>\@" . $domain . "<BR>"; 

        return $out;
}

=head2 get_local_networks

Return a <br> delimited string of all the networks this SMEServer is 
serving.

=cut

sub get_local_networks {
    my $fm = shift;

    my @nets = $networks->get_all_by_prop('type' => 'network');

    my $numNetworks = @nets;
    if ($numNetworks == 0) {
        return  $fm->localise('NO_NETWORKS');
    }
    else {
        my $out = "";
        foreach my $network (sort @nets) {
            if ($out ne "") {
                $out .= "<BR>";
            }

            $out .= $network->key."/" . get_net_prop($fm, $network->key, 'Mask');

            if ( defined get_net_prop($fm, $network->key, 'Router') ) {
                $out .= " via " . get_net_prop ($fm, $network->key, 'Router'); 
            }
        }
        return $out;
    }

}


=head2 get_local_domain

Get the local domain name

=cut

sub get_local_domain
{
    return (get_value('','DomainName'));
}

=head2 get_public_ip_address

Get the public IP address, if it is set. Note that this will only be set
for ServiceLink customers.

=cut

sub get_public_ip_address
{
    my $self = shift;
    my $sysconfig = $db->get('sysconfig');
    if ($sysconfig)
    {
        my $publicIP = $sysconfig->prop('PublicIP');
        if ($publicIP)
        {
            return $publicIP;
        }
    }
    return undef;
}

=head2 print_page

output the whole page we want to show

=cut

sub print_page {
    my $self = shift;

    print "<table>";
    print_header($self,'NETWORKING_PARAMS' );
    print_row($self,'SERVER_MODE', (get_value($self,'SystemMode' )|| '') );
    print_row($self,'LOCAL_IP_ADDRESS_SUBNET_MASK', get_value($self,'LocalIP').'/'.get_value($self,'LocalNetmask') );
    my $publicIP = $self->get_public_ip_address;
    if ($publicIP)
    {
        $self->print_row('INTERNET_VISIBLE_ADDRESS', $publicIP);
    }

    print_gateway_stanza($self);
    print_serveronly_stanza($self);
    print_row($self,'ADDITIONAL_LOCAL_NETWORKS', get_local_networks($self) );
    print_dhcp_stanza($self);

    print_header($self, 'SERVER_NAMES' );
    print_row($self,'DNS_SERVER', get_value('','LocalIP') );
    print_row($self,'WEB_SERVER', 'www.'.get_local_domain() );

    my $port = $db->get_prop("squid", "TransparentPort") || 3128;
    print_row($self,'PROXY_SERVER', 'proxy.'.get_local_domain().":$port" );

    print_row($self,'FTP_SERVER', 'ftp.'.get_local_domain() );
    print_row($self,'SMTP_POP_AND_IMAP_MAIL_SERVERS', 'mail.'.get_local_domain() );

    print_header($self,'DOMAIN_INFORMATION' );
    print_row($self,'PRIMARY_DOMAIN', get_value('','DomainName') );
    print_row($self,'VIRTUAL_DOMAINS', gen_domains($self));
    print_row($self,'PRIMARY_WEB_SITE', 'http://www.'.get_value('','DomainName') );
    print_row($self,'MITEL_NETWORKS_SME_SERVER_MANAGER',
	'https://'. (get_value('','SystemName') || 'localhost').'/server-manager/'  );
    print_row($self,'MITEL_NETWORKS_SME_SERVER_USER_PASSWORD_PANEL',
	'https://'. (get_value($self,'SystemName') || 'localhost').'/user-password/' );
    print_row($self,'EMAIL_ADDRESSES', gen_email_addresses($self) );
    print "</table>";
}

1;

