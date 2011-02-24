#!/usr/bin/perl -w 

#
# $Id: localnetworks.pm,v 1.32 2004/08/27 17:27:30 msoulier Exp $
#

package    esmith::FormMagick::Panel::localnetworks;

use strict;

use esmith::FormMagick;
use esmith::NetworksDB;
use esmith::ConfigDB;
use esmith::HostsDB;
use esmith::cgi;
use esmith::util;
use File::Basename;
use Exporter;
use Carp;
use Net::IPv4Addr;

our @ISA = qw(esmith::FormMagick Exporter);

our @EXPORT = qw(
  print_network_table
  ip_number_or_blank
  subnet_mask
  add_network
  remove_network
  show_remove_network_summary
);

our $VERSION = sprintf '%d.%03d', q$Revision: 1.32 $ =~ /: (\d+).(\d+)/;

=pod 

=head1 NAME

esmith::FormMagick::Panels::localnetworks - useful panel functions

=head1 SYNOPSIS

    use esmith::FormMagick::Panels::localnetworks;

    my $panel = esmith::FormMagick::Panel::localnetworks->new();
    $panel->display();

=head1 DESCRIPTION


=head2 new();

Exactly as for esmith::FormMagick

=begin testing

$ENV{ESMITH_ACCOUNT_DB} = "10e-smith-base/accounts.conf";
$ENV{ESMITH_CONFIG_DB} = "10e-smith-base/configuration.conf";
$ENV{ESMITH_NETWORKS_DB} = "10e-smith-base/networks.conf";

use_ok('esmith::FormMagick::Panel::localnetworks');
use vars qw($panel);
ok($panel = esmith::FormMagick::Panel::localnetworks->new(), 
    "Create panel object");
isa_ok($panel, 'esmith::FormMagick::Panel::localnetworks');

=end testing

=cut

sub new
{
    shift;
    my $self = esmith::FormMagick->new();
    $self->{calling_package} = (caller)[0];
    bless $self;
    return $self;
}

=head1 HTML GENERATION ROUTINES

Routines for generating chunks of HTML needed by the panel.

=head2 print_user_table

Prints out the user table on the front page.

=for testing
my $fm = esmith::FormMagick::Panel::localnetworks->new();
$fm->{cgi} = CGI->new();
can_ok('main', 'print_network_table');
print_network_table($fm);
like($_STDOUT_, qr/NUMBER_OF_HOSTS/, "saw hosts table");

=cut

sub print_network_table
{
    my $self = shift;
    my $q    = $self->{cgi};

    my $network_db = esmith::NetworksDB->open();
    my @networks = $network_db->get_all_by_prop( type => 'network' );
    unless (@networks)
    {
        print $q->h3 ( $self->localise('NO_ADDITIONAL_NETWORKS') );
        return "";
    }

    print $q->start_Tr, "\n";
    print $q->start_td, "\n";
    print $q->start_table( { class => "sme-border" } ), "\n";

    my $remove = $self->localise('REMOVE');

    print $q->Tr (
        esmith::cgi::genSmallCell(
            $q, ( $self->localise('NETWORK') ), "header"
        ),
        esmith::cgi::genSmallCell(
            $q, ( $self->localise('SUBNET_MASK') ), "header"
        ),
        esmith::cgi::genSmallCell(
            $q, ( $self->localise('NUMBER_OF_HOSTS') ), "header"
        ),
        esmith::cgi::genSmallCell(
            $q, ( $self->localise('ROUTER') ), "header"
        ),
        esmith::cgi::genSmallCell(
            $q, ( $self->localise('ACTION') ), "header"
        )
      ),
      "\n";

    my $scriptname = basename($0);

    foreach my $n ( sort by_key @networks )
    {
        my $network   = $n->key();
        my $subnet    = $n->prop('Mask');
        my $router    = $n->prop('Router');
        my $removable = $n->prop('Removable') || "yes";
        my $system    = $n->prop('SystemLocalNetwork') || "no";
        if ( $system eq "yes" )
        {
            $removable = "no";
        }
        my $params = $self->build_network_cgi_params($network);
        my $link   =
          ( $removable eq "no" )
          ? '&nbsp;'
          : $q->a( { -href => "$scriptname?$params&wherenext=Remove" },
            $remove );
        my ($num_hosts) = esmith::util::computeHostRange( $network, $subnet );
        print $q->Tr (
            esmith::cgi::genSmallCell( $q, $network,           "normal" ),
            esmith::cgi::genSmallCell( $q, $subnet,            "normal" ),
            esmith::cgi::genSmallCell( $q, $num_hosts,         "normal" ),
            esmith::cgi::genSmallCell( $q, $n->prop('Router'), "normal" ),
            esmith::cgi::genSmallCell( $q, $link,              "normal" )
        );
    }

    print $q->end_table, "\n";
    print $q->end_td,    "\n";
    print $q->end_Tr,    "\n";

    return "";
}

sub by_key
{
    $a->key() cmp $b->key();
}

sub build_network_cgi_params
{
    my ( $fm, $network, $oldprops ) = @_;

    my %props = (
        page       => 0,
        page_stack => "",
        ".id"      => $fm->{cgi}->param('.id') || "",
        network    => $network,
    );

    return $fm->props_to_query_string( \%props );
}

sub show_remove_network_summary
{
    my $self    = shift;
    my $q       = $self->{cgi};
    my $network = $q->param('network');

    my $network_db = esmith::NetworksDB->open();
    my $record     = $network_db->get($network);
    my $subnet     = $record->prop('Mask');
    my $router     = $record->prop('Router');

    print $q->Tr(
        $q->td(
            { -class => 'sme-noborders-label' },
            $self->localise('NETWORK')
        ),
        $q->td( { -class => 'sme-noborders-content' }, $network )
      ),
      "\n";
    print $q->Tr(
        $q->td(
            { -class => 'sme-noborders-label' },
            $self->localise('SUBNET_MASK')
        ),
        $q->td( { -class => 'sme-noborders-content' }, $subnet )
      ),
      "\n";
    print $q->Tr(
        $q->td(
            { -class => 'sme-noborders-label' }, $self->localise('ROUTER')
        ),
        $q->td( { -class => 'sme-noborders-content' }, $router )
      ),
      "\n";
    if ($self->hosts_on_network($network, $subnet))
    {
        print $q->Tr(
            $q->td({-colspan => 2},
                $self->localise('REMOVE_HOSTS_DESC')));
        print $q->Tr(
            $q->td({-class => 'sme-noborders-label'},
                $self->localise('REMOVE_HOSTS_LABEL')),
            $q->td({-class => 'sme-noborders-content'},
                $q->checkbox(-name => 'delete_hosts',
                             -checked=>1,
                             -value=>'ON',
                             -label => '')));
    }
    print $q->table(
        { -width => '100%' },
        $q->Tr(
            $q->th(
                { -class => 'sme-layout' },
                $q->submit(
                    -name  => 'cancel',
                    -value => $self->localise('CANCEL')
                ),
                ' ',
                $q->submit(
                    -name  => 'remove',
                    -value => $self->localise('REMOVE')
                )
            )
        )
      ),
      "\n";

    # Clear these values to prevent collisions when the page reloads.
    $q->delete("cancel");
    $q->delete("remove");

    return undef;
}

=head1 VALIDATION ROUTINES

=head2 ip_number_or_blank

The router field may either contain an ip address or may be blank.

=for testing
is  (ip_number_or_blank($panel, ''), "OK",        "blank IP address is OK");
is  (ip_number_or_blank($panel, '1.2.3.4'), "OK", "IP dress is OK");
isnt(ip_number_or_blank($panel, '1.2.3.4000'), "OK", "invalid IP address");

=cut

#sub ip_number_or_blank {
#    my ($fm, $data) = @_;
#    if (CGI::FormMagick::Validator::ip_number($fm, $data) eq "OK"
#        or $data eq "") {
#        return "OK";
#    } else {
#        return "INVALID_IP_ADDRESS";
#    }
#}

sub subnet_mask
{
    my ( $fm, $data ) = @_;
    if ( CGI::FormMagick::Validator::ip_number( $fm, $data ) eq "OK" )
    {
        return "OK";
    }
    else
    {
        return "INVALID_SUBNET_MASK";
    }
}

=head1 ADDING AND REMOVING NETWORKS 

=head2 add_network()

=cut

sub add_network
{
    my ($fm)           = @_;
    my $networkAddress = $fm->{cgi}->param('networkAddress');
    my $networkMask    = $fm->{cgi}->param('networkMask');
    my $networkRouter  = $fm->{cgi}->param('networkRouter');

    my $network_db = esmith::NetworksDB->open()
      || esmith::NetworksDB->create();
    my $config_db = esmith::ConfigDB->open();

    my $localIP      = $config_db->get('LocalIP');
    my $localNetmask = $config_db->get('LocalNetmask');

    my ( $localNetwork, $localBroadcast ) =
      esmith::util::computeNetworkAndBroadcast( $localIP->value(),
        $localNetmask->value() );

    my ( $routerNetwork, $routerBroadcast ) =
      esmith::util::computeNetworkAndBroadcast( $networkRouter,
        $localNetmask->value() );

    # Note to self or future developers:
    # the following tests should probably be validation routines
    # in the form itself, but it just seemed too fiddly to do that
    # at the moment.  -- Skud 2002-04-11

    if ( $routerNetwork ne $localNetwork )
    {
        $fm->error('NOT_ACCESSIBLE_FROM_LOCAL_NETWORK');
        return;
    }

    my ( $network, $broadcast ) =
      esmith::util::computeNetworkAndBroadcast( $networkAddress, $networkMask );

    if ( $network eq $localNetwork )
    {
        $fm->error('NETWORK_ALREADY_LOCAL');
        return;
    }

    if ( $network_db->get($network) )
    {
        $fm->error('NETWORK_ALREADY_ADDED');
        return;
    }

    $network_db->new_record(
        $network,
        {
            Mask   => $networkMask,
            Router => $networkRouter,
            type   => 'network',
        }
    );

    # Untaint $network before use in system()
    $network =~ /(.+)/;
    $network = $1;
    system( "/sbin/e-smith/signal-event", "network-create", $network ) == 0
      or ( $fm->error('ERROR_CREATING_NETWORK') and return undef );

    my ( $totalHosts, $firstAddr, $lastAddr ) =
      esmith::util::computeHostRange( $network, $networkMask );

    my $msg;
    if ( $totalHosts == 1 )
    {
        $msg = $fm->localise(
            'SUCCESS_SINGLE_ADDRESS',
            {
                network       => $network,
                networkMask   => $networkMask,
                networkRouter => $networkRouter
            }
        );
        $fm->success($msg);
    }
    elsif (( $totalHosts == 256 )
        || ( $totalHosts == 65536 )
        || ( $totalHosts == 16777216 ) )
    {
        $msg = $fm->localise(
            'SUCCESS_NETWORK_RANGE',
            {
                network       => $network,
                networkMask   => $networkMask,
                networkRouter => $networkRouter,
                totalHosts    => $totalHosts,
                firstAddr     => $firstAddr,
                lastAddr      => $lastAddr
            }
        );
        $fm->success($msg);
    }
    else
    {
        my $simpleMask =
          esmith::util::computeLocalNetworkPrefix( $network, $networkMask );
        $msg = $fm->localise(
            'SUCCESS_NONSTANDARD_RANGE',
            {
                network       => $network,
                networkMask   => $networkMask,
                networkRouter => $networkRouter,
                totalHosts    => $totalHosts,
                firstAddr     => $firstAddr,
                lastAddr      => $lastAddr,
                simpleMask    => $simpleMask
            }
        );
        $fm->success($msg);
    }
}

=head2 remove_network()

=cut

sub remove_network
{
    my ($self) = @_;

    my $network    = $self->cgi->param('network');
    my $delete_hosts = $self->cgi->param('delete_hosts') || "";
    my $network_db = esmith::NetworksDB->open();

    unless ( $self->{cgi}->param("cancel") )
    {
        if ( my $record = $network_db->get($network) )
        {
            $record->set_prop( type => 'network-deleted' );
	    # Untaint $network before use in system()
	    $network =~ /(.+)/;
	    $network = $1;
            if (
                system(
                    "/sbin/e-smith/signal-event", "network-delete",
                    $network
                ) == 0
              )
            {
                my $networkMask   = $record->prop('Mask')   || "";
                my $networkRouter = $record->prop('Router') || "";
                if ($delete_hosts)
                {
                    my @hosts_to_delete = $self->hosts_on_network(
                        $network, $networkMask);
                    foreach my $host (@hosts_to_delete)
                    {
                        $host->delete;
                    }
                }
                $record->delete;
                my $msg = $self->localise(
                    'SUCCESS_REMOVED_NETWORK',
                    {
                        network       => $network,
                        networkMask   => $networkMask,
                        networkRouter => $networkRouter
                    }
                );
                $self->success($msg);
            }
            else
            {
                $self->error("ERROR_DELETING_NETWORK");
            }
        }
        else
        {
            $self->error("NO_SUCH_NETWORK");
        }
    }
}

=head2 hosts_on_network

This method takes a network address, and a netmask, and audits the hosts
database looking for hosts on that network. In a scalar context it returns the
number of hosts found on that network. In a list context it returns the host
records.

=cut

sub hosts_on_network
{
    my $self = shift;
    my $network = shift;
    my $netmask = shift;

    die if not $network and $netmask;

    my $cidr = "$network/$netmask";
    my $hosts = esmith::HostsDB->open;
    my @localhosts = grep { $_->prop('HostType') eq 'Local' } $hosts->hosts;
    my @hosts_on_network = ();
    foreach my $host (@localhosts)
    {
        my $ip = $host->prop('InternalIP') || "";
        if ($ip)
        {
            if (Net::IPv4Addr::ipv4_in_network($cidr, $ip))
            {
                push @hosts_on_network, $host;
            }
        }
    }
    return @hosts_on_network if wantarray;
    return scalar @hosts_on_network;
}

1;
