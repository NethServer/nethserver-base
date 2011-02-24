#!/usr/bin/perl -w 

#----------------------------------------------------------------------
# $Id: reboot.pm,v 1.3 2002/05/22 21:58:07 apc Exp $
#----------------------------------------------------------------------
# copyright (C) 2002-2005 Mitel Networks Corporation
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
package    esmith::FormMagick::Panel::reboot;

use strict;

use esmith::FormMagick;
use esmith::util;
use File::Basename;
use Exporter;
use Carp;

our @ISA = qw(esmith::FormMagick Exporter);

our @EXPORT = qw( change_settings
);

our $VERSION = sprintf '%d.%03d', q$Revision: 1.3 $ =~ /: (\d+).(\d+)/;

# {{{ header

=pod 

=head1 NAME

esmith::FormMagick::Panels::reboot - useful panel functions

=head1 SYNOPSIS

    use esmith::FormMagick::Panels::reboot;

    my $panel = esmith::FormMagick::Panel::reboot->new();
    $panel->display();

=head1 DESCRIPTION

=cut

# {{{ new

=head2 new();

Exactly as for esmith::FormMagick

=begin testing


use_ok('esmith::FormMagick::Panel::reboot');
use vars qw($panel);
ok($panel = esmith::FormMagick::Panel::reboot->new(), "Create panel object");
isa_ok($panel, 'esmith::FormMagick::Panel::reboot');

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


=head1 ACTION

=head2 change_settings

	Reboot or halt the machine

=cut



sub change_settings {
    my ($fm) = @_;

    my $q = $fm->{'cgi'};

    my $function = $q->param ('function');

    my $debug = $q->param('debug');
   
    if ($function eq "reboot") {
        $fm->{cgi}->param( -name  => 'initial_message', -value => 'REBOOT_SUCCEEDED');
        $fm->{cgi}->param( -name => 'wherenext', -value => 'Reboot' );
        unless ($debug) {
           system( "/sbin/e-smith/signal-event", "reboot" ) == 0
                 or die ("Error occurred while rebooting.\n");
                 }
    } elsif ($function eq 'shutdown') {
        $fm->{cgi}->param( -name  => 'initial_message', -value => 'HALT_SUCCEEDED');
        $fm->{cgi}->param( -name => 'wherenext', -value => 'Shutdown' );
        unless ($debug) {
           system( "/sbin/e-smith/signal-event", "halt" ) == 0
                 or die ("Error occurred while halting.\n");
           }
    } elsif ($function eq 'reconfigure') {
        $fm->{cgi}->param( -name  => 'initial_message', -value => 'RECONFIGURE_SUCCEEDED');
        $fm->{cgi}->param( -name => 'wherenext', -value => 'Reconfigure' );
        unless ($debug) {
           system( "/sbin/e-smith/signal-event", "post-upgrade" ) == 0
                 or die ("Error occurred while running post-upgrade.\n");
           system( "/sbin/e-smith/signal-event", "reboot" ) == 0
                 or die ("Error occurred while rebooting.\n");
           }
    }
}


1;

