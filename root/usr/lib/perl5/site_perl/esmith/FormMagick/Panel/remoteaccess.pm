#!/usr/bin/perl -w 

#----------------------------------------------------------------------
# $Id: remoteaccess.pm,v 1.42 2005/03/19 01:00:54 charlieb Exp $
#----------------------------------------------------------------------
#----------------------------------------------------------------------
# copyright (C) 1999-2003 Mitel Networks Corporation
# 
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
# 		
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# 		
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307  USA
#----------------------------------------------------------------------

package    esmith::FormMagick::Panel::remoteaccess;

use strict;
use esmith::ConfigDB;
use esmith::FormMagick;
use esmith::util;
use esmith::cgi;
use File::Basename;
use Exporter;
use Carp;

our @ISA = qw(esmith::FormMagick Exporter);

our @EXPORT = qw(get_ssh_permit_root_login get_ssh_access get_telnet_mode
		 change_settings get_ftp_access get_pptp_sessions get_ftp_password_login_access
		  get_value get_prop get_ssh_password_auth zero_or_positive 
		show_valid_from_list add_new_valid_from remove_valid_from
		validate_network_and_mask ip_number_or_blank subnet_mask_or_blank
		show_telnet_section get_serial_console show_ftp_section
		get_ipsecrw_sessions show_ipsecrw_section
);



our $VERSION = sprintf '%d.%03d', q$Revision: 1.42 $ =~ /: (\d+).(\d+)/;
our $db = esmith::ConfigDB->open 
    || warn "Couldn't open configuration database (permissions problems?)";


# {{{ header

=pod 

=head1 NAME

esmith::FormMagick::Panels::remoteaccess - useful panel functions

=head1 SYNOPSIS

    use esmith::FormMagick::Panels::remoteaccess;

    my $panel = esmith::FormMagick::Panel::remoteaccess->new();
    $panel->display();

=head1 DESCRIPTION

=cut

# {{{ new

=head2 new();

Exactly as for esmith::FormMagick

=begin testing

$ENV{ESMITH_CONFIG_DB} = "10e-smith-base/configuration.conf";

use_ok('esmith::FormMagick::Panel::remoteaccess');
use vars qw($panel);
ok($panel = esmith::FormMagick::Panel::remoteaccess->new(), 
	"Create panel object");
isa_ok($panel, 'esmith::FormMagick::Panel::remoteaccess');

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
  my ($self, $item, $prop) = @_;
  warn "You must specify a record key"    unless $item;
  warn "You must specify a property name" unless $prop;
  my $record = $db->get($item) or warn "Couldn't get record for $item";
  return $record ? $record->prop($prop) : undef;
}

=head2 get_value ITEM

A simple accessor for esmith::ConfigDB::Record::value

=cut

sub get_value {
  my $self = shift;
  my $item = shift;
  return ($db->get($item)->value());
}

=head2 get_ftp_access

Returns "normal", "private" or "off" depending on the 'access' and 'status' properties
of the "ftp" config file variable

=cut

sub get_ftp_access
{
    my $status = get_prop('','ftp','status') || 'disabled';
    return 'off' unless $status eq 'enabled';

    my $access = get_prop('','ftp','access') || 'private';
    return ($access  eq 'public') ? 'normal' : 'private';
}

=head2 get_pptp_sessions

Get the # of pptp sessions defined in the sessions property of the pptp config file variable

=cut

  sub get_pptp_sessions {
  my $status = get_prop('','pptpd','status');
  if (defined($status) && ($status eq 'enabled')) {
    
    return(get_prop('','pptpd','sessions') || 'no');
  }
  else {
    return('0');
  }
}

=head2 get_ssh_permit_root_login

returns 'yes' or 'no' depending on whether ssh permit root login is enabled

=cut

sub get_ssh_permit_root_login
{
    return(get_prop('','sshd','PermitRootLogin') || 'no');
}

=head2 get_ssh_password_auth

Returns 'no' or 'yes' depending on whether ssh password auth is enabled

=cut

sub get_ssh_password_auth
{
    return(get_prop('','sshd','PasswordAuthentication') || 'yes');
}

=head2 get_ssh_access

Returns 'public' 'private' or 'off', depending on the current ssh server mode.

=cut

sub get_ssh_access {

  my $status = get_prop('','sshd','status');
  if (defined($status) && ($status eq 'enabled')) {
    my $access = get_prop('','sshd','access');
    $access = ($access eq 'public') ? 'public' : 'private';
    return($access);
  }
  else {
    return('off');
  }
}

=head2 get_ssh_port

Get the tcp port defined in the TCPPort propery
in the sshd config file variable

=cut

sub get_ssh_port
{
    return(get_prop('$self','sshd','TCPPort') || '22');
}

=head2 get_ftp_password_login_access

Returns "public" or "private" depending on the 'status' and 'LoginAccess' properties
of the "ftp" config file variable

=cut

sub get_ftp_password_login_access
{
    my $status = get_prop('','ftp','status') || 'disabled';
    return 'private' unless $status eq 'enabled';

    my $access = get_prop('','ftp','LoginAccess') || 'private';

    return ($access  eq 'public') ? 'public' : 'private';
}

=head2 get_telnet_mode

Returns "public", "private" or "off" depending on the current telnet configuration

=cut

sub get_telnet_mode {
  my $telnet = $db->get('telnet');
  return('off') unless $telnet;
  my $status = $telnet->prop('status') || 'disabled';
  return('off') unless $status eq 'enabled';
  my $access = $telnet->prop('access') || 'private';
  return ($access eq "public") ? "public" : "private";
}

=head2 get_serial_console

Returns "disabled" or the serial device on which a login console is
enabled.

=cut

sub get_serial_console
{
    my $status = get_prop('','serial-console','status') || 'disabled';
    return 'disabled' unless $status eq 'enabled';

    return get_prop('','serial-console','Device') || 'ttyS1';
}

sub show_telnet_section
{
    my $self = shift;
    my $q = $self->cgi;
    my $mode = get_telnet_mode();

    # Don't show telnet setting if it is off
    return '' if $mode eq 'off';

    my %options = (
	public => $self->localise('NETWORKS_ALLOW_PUBLIC'), 
	private => $self->localise('NETWORKS_ALLOW_LOCAL'), 
	off => $self->localise('NO_ACCESS'),
    ); 

    print $q->Tr(
      $q->td({-colspan => 2},
      $q->p( 
	$q->table(
	  $q->Tr(
	    $q->td({-colspan => 2},
	      $q->span({-class => "error-noborders"},
		$self->localise('DESC_TELNET_ACCESS')))),
	  $q->Tr(
	    $q->td({-class => "sme-noborders-label"}, 
	      $self->localise('LABEL_TELNET_ACCESS')),
	    $q->td({-class => "sme-noborders-content"},
	      $q->popup_menu(-name => 'TelnetAccess', 
		-values => [ keys %options ],
		-labels => \%options,
		-default => $mode)))
	)
      )
      )
    );
    return '';	    
}


sub show_ftp_section
{
    my $self = shift;
    my $q = $self->{cgi};

    # Don't show ftp setting unless the property exists
    return '' unless $db->get('ftp');

    my %options = (
	normal => $self->localise('NETWORKS_ALLOW_PUBLIC'), 
	private => $self->localise('NETWORKS_ALLOW_LOCAL'), 
	off => $self->localise('NO_ACCESS'),
    ); 

    my %loginOptions = (
	private => $self->localise('PASSWORD_LOGIN_PRIVATE'),
	public  => $self->localise('PASSWORD_LOGIN_PUBLIC'),
    );

    print $q->Tr(
      $q->td({-colspan => 2},
      $q->p( 
	$q->table(
	  $q->Tr(
	    $q->td({-colspan => 2},
	      $q->span({-class => "sme-noborders"},
		$self->localise('DESC_FTP_ACCESS')))),
	  $q->Tr(
	    $q->td({-class => "sme-noborders-label"}, 
	      $self->localise('LABEL_FTP_ACCESS')),
	    $q->td({-class => "sme-noborders-content"},
	      $q->popup_menu(-name => 'FTPAccess', 
		-values => [ keys %options ],
		-labels => \%options,
		-default => get_ftp_access()))),
	  $q->Tr(
	    $q->td({-colspan => 2},
	      $q->span({-class => "sme-noborders"},
		$self->localise('DESC_FTP_LOGIN')))),
	  $q->Tr(
	    $q->td({-class => "sme-noborders-label"}, 
	      $self->localise('LABEL_FTP_LOGIN')),
	    $q->td({-class => "sme-noborders-content"},
	      $q->popup_menu(-name => 'FTPPasswordLogin', 
		-values => [ keys %loginOptions ],
		-labels => \%loginOptions,
		-default => get_ftp_password_login_access())))
	)
      )
      )
    );
    return '';	    
}

=pod

=head2 zero_or_positive

Validate that the input is a number >= 0.

=cut

sub zero_or_positive
{
  my $self = shift;
  my $val = shift || 0;

  return 'OK' if($val =~ /^\d+$/ and $val >= 0);
  return $self->localise('VALUE_ZERO_OR_POSITIVE');
}

=pod

=head2 _get_valid_from

Reads the ValidFrom property of config entry httpd-admin and returns a list
of the results. Private method.

=for testing
ok($panel->_get_valid_from(), "_get_valid_from");

=cut

sub _get_valid_from
{
	my $self = shift;

	my $rec = $db->get('httpd-admin');
	return undef unless($rec);
	my @vals = (split ',', ($rec->prop('ValidFrom') || ''));
	return @vals;
}

=pod

=head2 add_new_valid_from

Adds a new ValidFrom property in httpd-admin.

=for testing
$panel->{cgi} = CGI->new();
$panel->{cgi}->param(-name=>'validFromNetwork',-value=>'1.2.3.4');
$panel->{cgi}->param(-name=>'validFromMask',-value=>'255.255.255.255');
is($panel->add_new_valid_from(), '', 'add_new_valid_from');

=cut

sub ip_number_or_blank 
{
    my $self = shift;
    my $ip = shift;

    if (!defined($ip) || $ip eq "")
    {
	return 'OK';
    }
    return CGI::FormMagick::Validator::ip_number($self, $ip);
}

sub subnet_mask_or_blank 
{
    my ($self, $mask) = @_;

    if ($self->ip_number_or_blank($mask) eq 'OK') 
    {
        return "OK";
    } 
    return "INVALID_SUBNET_MASK";
}

sub validate_network_and_mask
{
  my $self = shift;
  my $mask = shift || "";

  my $net = $self->cgi->param('validFromNetwork') || "";
  if ($net xor $mask)
  {
    return $self->localise('ERR_INVALID_PARAMS');
  }
  return 'OK';
}

sub add_new_valid_from
{
	my $self = shift;
	my $q = $self->{cgi};

	my $net = $q->param('validFromNetwork');
	my $mask = $q->param('validFromMask');

	# do nothing if no network was added
	return 1 unless ($net && $mask);

	my $rec = $db->get('httpd-admin');
	unless ($rec)
	{
		return $self->error('ERR_NO_RECORD');
	}

	my $prop = $rec->prop('ValidFrom') || '';

	my @vals = split /,/, $prop;
	return '' if (grep /^$net\/$mask$/, @vals); # already have this entry

	if ($prop ne '')
	{
		$prop .= ",$net/$mask";
	}
	else
	{
		$prop = "$net/$mask";
	}
	$rec->set_prop('ValidFrom', $prop);
	$q->delete('validFromNetwork');
	$q->delete('validFromMask');
	return 1;
}

=pod

=head2 remove_valid_from

Remove the specified net/mask from ValidFrom

=for testing
$panel->{cgi}->param(-name=>'validFromNetwork', -value=>'1.2.3.4');
$panel->{cgi}->param(-name=>'validFromMask', -value=>'255.255.255.255');
is($panel->remove_valid_from(), '', 'remove_valid_from');

=cut

sub remove_valid_from
{
	my $self = shift;
	my $q = $self->{cgi};

	my @remove = $q->param('validFromRemove');
	my @vals = $self->_get_valid_from();

	foreach my $entry (@remove)
	{
	    return undef unless $entry;

	    my ($net, $mask) = split (/\//, $entry);
	    
	    unless (@vals)
	    {
		print STDERR "ERROR: unable to load ValidFrom property from conf db\n";
		return undef;
	    }

	    # what if we don't have a mask because someone added an entry from
	    # the command line? by the time we get here, the panel will have
	    # added a 32 bit mask, so we don't know for sure if the value in db
	    # is $net alone or $net/255.255.255.255. we have to check for both
	    # in this special case...
	    @vals = (grep { $entry ne $_ && $net ne $_ } @vals);
	}

	my $prop;
	if (@vals)
	{
		$prop = join ',',@vals;
	}
	else
	{
		$prop = '';
	}
	$db->get('httpd-admin')->set_prop('ValidFrom', $prop);

	return 1;
}

=pod

=head2 show_valid_from_list

Displays a table of the ValidFrom networks for httpd-admin.

=for testing
$panel->{cgi}->param(-name=>'validFromNetwork', -value=>'5.4.3.2');
$panel->{cgi}->param(-name=>'validFromMask', -value=>'255.255.255.255');
$panel->add_new_valid_from();
$panel->{source} = qq(<form><page name="RemoveValidFrom"></page></form>);
$panel->parse_xml();
$panel->show_valid_from_list();
like($_STDOUT_, qr/VALIDFROM_DESC/, 'show_valid_from_list');
like($_STDOUT_, qr/5.4.3.2/, '  .. saw the network listed');
like($_STDOUT_, qr/REMOVE/, ' .. saw the remove button');
$panel->remove_valid_from();

=cut

sub show_valid_from_list
{
    my $self = shift;
    my $q = $self->{cgi};

    print '<tr><td colspan=2>',$q->p($self->localise('VALIDFROM_DESC')),'</td></tr>';

    my @vals = $self->_get_valid_from();
    if (@vals)
    {
	print '<tr><td colspan=2>',
            $q->start_table({class => "sme-border"}),"\n";
        print $q->Tr(
                     esmith::cgi::genSmallCell($q, $self->localise('NETWORK'),"header"),
                     esmith::cgi::genSmallCell($q, $self->localise('SUBNET_MASK'),"header"),
                     esmith::cgi::genSmallCell($q, $self->localise('NUM_OF_HOSTS'),"header"),
                     esmith::cgi::genSmallCell($q, $self->localise('REMOVE'),"header"));

	my @cbGroup = $q->checkbox_group(-name => 'validFromRemove',
		-values => [@vals], -labels => { map {$_ => ''} @vals });
        foreach my $val (@vals)
        {
            my ($net, $mask) = split '/', $val;
            $mask = '255.255.255.255' unless ($mask);
            my ($numhosts,$a,$b) = esmith::util::computeHostRange($net,$mask);
            print $q->Tr(
                         esmith::cgi::genSmallCell($q, $net, "normal"),
                         esmith::cgi::genSmallCell($q, $mask, "normal"),
                         esmith::cgi::genSmallCell($q, $numhosts, "normal"),
                         esmith::cgi::genSmallCell($q, shift(@cbGroup), 
			    "normal")); 
        }
        print '</table></td></tr>';
    }
    else
    {
        print $q->Tr($q->td($q->b($self->localise('NO_ENTRIES_YET'))));
    }
    return '';
}

=head1 ACTION

=head2 change_settings

	If everything has been validated, properly, go ahead and set the new settings

=cut



sub change_settings {
    my ($self) = @_;

    my %conf;

    my $q = $self->{'cgi'};

	# Don't process the form unless we clicked the Save button. The event is
	# called even if we chose the Remove link or the Add link.
	return unless($q->param('Next') eq $self->localise('SAVE'));

    my $access = ($q->param ('TelnetAccess') || 'off');
    my $sshaccess = ($q->param ('sshAccess') || 'off');
    my $sshPermitRootLogin = ($q->param ('sshPermitRootLogin') || 'no');
    my $sshPasswordAuthentication = ($q->param ('sshPasswordAuthentication') || 'no');
    my $sshTCPPort = ($q->param ('sshTCPPort') || '22');
    my $ftplogin = ($q->param ('FTPPasswordLogin') || 'private');
    my $ftpaccess = ($q->param ('FTPAccess') || 'off');
    my $pptpSessions = ($q->param ('pptpSessions') || '0');
#    my $serialConsole = ($q->param ('serialConsole') || 'disabled');

    #------------------------------------------------------------
    # Looks good; go ahead and change the access.
    #------------------------------------------------------------

    my $rec = $db->get('telnet');
    if($rec)
    {
    	if ($access eq "off")
    	{
	    $rec->set_prop('status','disabled');
    	}
    	else
      	{
	    $rec->set_prop('status','enabled');
	    $rec->set_prop('access', $access);
      	}
    }

    $rec = $db->get('sshd') || $db->new_record('sshd', {type => 'service'});
    $rec->set_prop('TCPPort', $sshTCPPort);
    $rec->set_prop('status', ($sshaccess eq "off" ? 'disabled' : 'enabled'));
    $rec->set_prop('access', $sshaccess);
    $rec->set_prop('PermitRootLogin', $sshPermitRootLogin);
    $rec->set_prop('PasswordAuthentication', $sshPasswordAuthentication);


    $rec = $db->get('ftp');
    if($rec)
    {
    	if ($ftpaccess eq "off")
    	{
	    $rec->set_prop('status', 'disabled');
	    $rec->set_prop('access', 'private');
	    $rec->set_prop('LoginAccess', 'private');
    	}
    	elsif ($ftpaccess eq "normal")
    	{
	    $rec->set_prop('status', 'enabled');
	    $rec->set_prop('access', 'public');
	    $rec->set_prop('LoginAccess', $ftplogin);
    	}
    	else
    	{
	    $rec->set_prop('status', 'enabled');
	    $rec->set_prop('access', 'private');
	    $rec->set_prop('LoginAccess', $ftplogin);
    	}
    }

    if ($pptpSessions == 0)
    {
	$db->get('pptpd')->set_prop('status', 'disabled');
    }
    else
    {
	$db->get('pptpd')->set_prop('status', 'enabled');
	$db->get('pptpd')->set_prop('sessions', $pptpSessions);
    }

# REMOVED by markk, May 16 2005 - see DPAR MN00084537
#    $rec = $db->get('serial-console');
#    unless($rec)
#    {
#	$rec = $db->new_record('serial-console', {type=>'service'});
#    }

#    if ($serialConsole eq 'disabled')
#    {
#	$rec->set_prop('status', 'disabled');
#    }
#    else
#    {
#	$rec->set_prop('status', 'enabled');
#	$rec->set_prop('Device', $serialConsole);
#    }

    $self->cgi->param(-name=>'wherenext', -value=>'First');

    unless ($self->add_new_valid_from)
    {
	return '';
    }

    unless ($self->remove_valid_from)
    {
	return '';
    }
  
    # reset ipsec roadwarrior CA,server,client certificates
    if ($q->param('ipsecrwReset')) {
	system('/sbin/e-smith/roadwarrior', 'reset_certs') == 0
	    or die "Error occurred while resetting ipsec certificates.\n";
	$q->param(-name=>'ipsecrwReset', -value=>'');
    }
    $self->set_ipsecrw_sessions;

    unless ( system( "/sbin/e-smith/signal-event", "remoteaccess-update" ) == 0 )
    {
        $self->error('ERROR_UPDATING_CONFIGURATION');
        return undef;
    }

    $self->success('SUCCESS');
}

sub get_ipsecrw_sessions 
{
  my $status = $db->get('ipsec')->prop('RoadWarriorStatus');
  if (defined($status) && ($status eq 'enabled')) {
    return($db->get('ipsec')->prop('RoadWarriorSessions') || '0');
  }
  else {
    return('0');
  }
}

sub show_ipsecrw_section
{
    my $self = shift;
    my $q = $self->cgi;

    # Don't show ipsecrw setting unless the status property exists
    return '' unless ($db->get('ipsec') 
			&& $db->get('ipsec')->prop('RoadWarriorStatus'));

    print $q->Tr(
      $q->td( {-colspan => 2},
      $q->p( 
	$q->table(
	  $q->Tr(
	    $q->td({-colspan => 2, -class => "sme-noborders"},
		$self->localise('DESC_IPSECRW'))),
	  $q->Tr(
	    $q->td({-class => "sme-noborders-label"}, 
	      $self->localise('LABEL_IPSECRW_SESS')),
	    $q->td({-class => "sme-noborders-content"},
	      $q->textfield(-name => 'ipsecrwSessions', 
		-value => get_ipsecrw_sessions(),
		-size => '3'))),
	  $q->Tr(
	    $q->td({-colspan => 2, -class => "sme-noborders"},
		$self->localise('DESC_IPSECRW_RESET'))),
	  $q->Tr(
	    $q->td({-class => "sme-noborders-label"}, 
	      $self->localise('LABEL_IPSECRW_RESET')),
	    $q->td({-class => "sme-noborders-content"},
	      $q->checkbox(-name => 'ipsecrwReset', -label => ''))),
	)
      )
      )
    );

    return '';
}

sub set_ipsecrw_sessions
{
    my $self = shift;
    my $q = $self->cgi;
    my $sessions = $q->param('ipsecrwSessions');
    if (defined $sessions)
    {
	$db->get('ipsec')->set_prop('RoadWarriorSessions', $sessions);
	if (int($sessions) > 0) {
	    $db->get('ipsec')->set_prop('RoadWarriorStatus', 'enabled');
	}
    }
    return '';
}

1;


