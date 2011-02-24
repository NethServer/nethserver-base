#!/usr/bin/perl -w 

#
# $Id: groups.pm,v 1.38 2005/05/12 21:44:29 charlieb Exp $
#

package esmith::FormMagick::Panel::groups;

use strict;

use esmith::FormMagick;
use esmith::ConfigDB;
use esmith::AccountsDB;
use File::Basename;
use Exporter;
use Carp;

our @ISA = qw(esmith::FormMagick Exporter);

our @EXPORT = qw(

  show_initial
  genUsers
  create_group
  modify_group
  delete_group
  validate_is_group
  validate_group_naming_conflict
  validate_group
  validate_group_length
  getNextFreeID
  validate_group_has_members
  print_group_delete_desc
  print_group_members
  print_group_name
  print_ibay_list
  get_accounts_prop
  get_description
  get_cgi_param
);

our $accounts = esmith::AccountsDB->open() || die "Couldn't open accounts";
our $db = esmith::ConfigDB->open || die "Couldn't open config db";

our $VERSION = sprintf '%d.%03d', q$Revision: 1.38 $ =~ /: (\d+).(\d+)/;


=pod 

=head1 NAME

esmith::FormMagick::Panels::groups - useful panel functions

=head1 SYNOPSIS

    use esmith::FormMagick::Panels::groups;

    my $panel = esmith::FormMagick::Panel::groups->new();
    $panel->display();

=head1 DESCRIPTION

=cut


=head2 new();

Exactly as for esmith::FormMagick

=begin testing

$ENV{ESMITH_ACCOUNT_DB} = "10e-smith-base/accounts.conf";
$ENV{ESMITH_CONFIG_DB} = "10e-smith-base/configuration.conf";

use_ok('esmith::FormMagick::Panel::groups');
use vars qw($panel);
ok($panel = esmith::FormMagick::Panel::groups->new(), "Create panel object");
isa_ok($panel, 'esmith::FormMagick::Panel::groups');

=end testing

=cut

sub new {
    shift;
    my $self = esmith::FormMagick->new();
    $self->{calling_package} = (caller)[0];
    bless $self;
    return $self;
}


=head1 ACCESSORS

=head2 get_cgi_param FM FIELD

Returns the named CGI parameter as a string

=cut

sub get_cgi_param {
    my $fm    = shift;
    my $param = shift;

    return ( $fm->{'cgi'}->param($param) );
}


=head2 get_accounts_prop ITEM PROP

A simple accessor for esmith::AccountsDB::Record::prop

=cut

sub get_accounts_prop {
    my $fm   = shift;
    my $item = shift;
    my $prop = shift;

    my $record = $accounts->get($item);

    if ($record) {
        return $record->prop($prop);
    }
    else {
        return '';
    }

}


=head2 get_description

Get the Description for the group named in the CGI argument "GroupName"

=cut

sub get_description {
    my $fm    = shift;
    my $group = $fm->{'cgi'}->param('groupName');
    return ( $fm->get_accounts_prop( $group, 'Description' ) );
}

=head1 ACTION


=head2 show_initial FM

Show the "start" page for this panel

=cut

sub show_initial () {
    my $fm = shift;
    my $q = $fm->{cgi};
    $q->Delete('groupName');

    my $params = $fm->build_cgi_params();

    my $numGroups = $accounts->groups;

    print $q->Tr($q->td(
	  "<p><a class=\"button-like\" href=\"groups?$params&wherenext=CreateGroup\">"
          . $fm->localise("GROUP_ADD")
          . "</a></p>"));

    if ( $numGroups == 0 ) {
        print $q->Tr($q->td(
	    '<p><b>' . $fm->localise("ACCOUNT_GROUP_NONE") . '</p></b>'));

    }
    else {
	print $q->Tr($q->td({-colspan => 2}, $fm->localise('CURRENT_LIST')));
        print $q->start_table({-CLASS => "sme-border"}),"\n";
	print "<tr><th class=\"sme-border\">"
	    . $fm->localise("GROUP")
	    . "</th> <th class=\"sme-border\">"
	    . $fm->localise('DESCRIPTION')
	    . "</th><th class=\"sme-border\" colspan=\"2\">"
	    . $fm->localise('ACTION')
	    . "</th></tr>";
        foreach my $group ( $accounts->groups() ) {
            $params = $fm->build_cgi_params( $group->key );
            print "<tr>" . "<td class=\"sme-border\">"
              . $group->key . "</td>" . "<td class=\"sme-border\">"
              . $group->prop('Description') . "</td>"
              . "<td class=\"sme-border\"><a href=\"groups?$params&wherenext=Modify\">"
              . $fm->localise("MODIFY") . "</a></td>"
              . "<td class=\"sme-border\"><a href=\"groups?$params&wherenext=Delete\">"
              . $fm->localise("REMOVE") . "</a>" . "</td></tr>";

        }
        print $q->end_table,"\n";
    }
    return;
}

=head2 create_group FM

Create a group

=cut

sub create_group {
    my $fm = shift;
    my $q  = $fm->{'cgi'};

    my $groupName = $q->param('groupName');
    my @members   = $q->param('groupMembers');
    my $members   = join ( ",", @members );

    my %props = (
        'type', 'group', 'Description',
        $q->param('groupDesc'), 'Members', $members
    );

    $accounts->new_record( $groupName, \%props );

    # Untaint groupName before use in system()
    ($groupName) = ($groupName =~ /^([a-z][\-\_\.a-z0-9]*)$/);
    $fm->clear_params();

    return system("/sbin/e-smith/signal-event", "group-create", "$groupName") ?
	$fm->error('CREATE_ERROR') : $fm->success('CREATED_GROUP');
}

=head2 modify_group FM

Modify a group's description and membership roster

=cut

sub modify_group {

    my $fm = shift;
    my $q  = $fm->{'cgi'};

    my @members   = $q->param('groupMembers');
    my $desc      = $q->param('groupDesc');
    my $groupName = $q->param('groupName');

    $accounts->get($groupName)->set_prop( 'Members', join ( ',', @members ) );
    $accounts->get($groupName)->set_prop( 'Description', $desc );

    # Untaint groupName before use in system()
    ($groupName) = ($groupName =~ /^([a-z][\-\_\.a-z0-9]*)$/);
    $fm->clear_params();
    return system("/sbin/e-smith/signal-event", "group-modify", "$groupName") ?
	$fm->error('MODIFY_ERROR') : $fm->success('MODIFIED_GROUP');
}

=head2 delete_group FM

Delete a group and move all of its ibays to the 'admin' group.

=cut

sub delete_group {

    my $fm = shift;
    my $q  = $fm->{'cgi'};

    my $groupName = $q->param('groupName');

    $accounts->get($groupName)->set_prop( 'type', 'group-deleted' );


    # Untaint groupName before use in system()
    ($groupName) = ($groupName =~ /^([a-z][\-\_\.a-z0-9]*)$/);
    $fm->clear_params();
    return (system("/sbin/e-smith/signal-event", "group-delete",
			    "$groupName") ||
		    !$accounts->get($groupName)->delete()) ?
	$fm->error('DELETE_ERROR') : $fm->success('DELETED_GROUP');
}


=head1 VALIDATION

=head2 validate_is_group FM GROUP

returns OK if GROUP is a current group. otherwisee returns "NOT_A_GROUP"

=begin testing

#ok($panel->validate_is_group('root') eq 'OK', "Root is a group");
ok($panel->validate_is_group('ro2ot') eq 'NOT_A_GROUP', "Ro2ot is not a group");

=end testing

=cut

sub validate_is_group () {
    my $fm    = shift;
    my $group = shift;

    my @groups = $accounts->groups();
    my %groups = map { $_->key => 1 } @groups;

    unless ( exists $groups{$group} ) {
        return ("NOT_A_GROUP");
    }
    return ("OK");

}


=head2 validate_group_naming_conflict FM GROUPNAME 

Returns "OK" if this group's name doesn't conflict with anything
Returns "PSEUDONYM_CONFLICT" if this name conflicts with a pseudonym
Returns "NAME_CONFLICT" if this group name conflicts with anything else

ok (undef, 'need testing for validate_naming_Conflicts');
=cut

sub validate_group_naming_conflict
{
    my $fm        = shift;
    my $groupName = shift;

    my $account = $accounts->get($groupName);
    my $type;

    if (defined $account)
    {
	$type = $account->prop('type');
    }
    elsif (defined getpwnam($groupName) || defined getgrnam($groupName))
    {
	$type = "system";
    }
    else
    {
	return('OK');
    }
    return $fm->localise('ACCOUNT_CONFLICT',
	    { group => $groupName, 
	      type => $type,
	    });
}

=head2 validate_group FM groupname

Returns OK if the group name contains only valid characters
Returns GROUP_NAMING otherwise

=being testing

ok(validate_group('','foo') eq 'OK', 'foo is a valid group);
ok(validate_group('','f&oo') eq 'GROUP_CONTAINS_INVALD', 'f&oo is not a valid group);

=end testing

=cut

sub validate_group {
    my $fm        = shift;
    my $groupName = shift;
    unless ( $groupName =~ /^([a-z][\-\_\.a-z0-9]*)$/ ) {
        return ('GROUP_NAMING');
    }
    return ('OK');
}


=head2 validate_group_length FM GROUPNAME

returns 'OK' if the group name is shorter than the maximum group name length
returns 'GROUP_TOO_LONG' otherwise

=begin testing

ok(($panel->validate_group_length('foo') eq 'OK'), "a short groupname passes");
ok(($panel->validate_group_length('fooooooooooooooooo') eq 'GROUP_TOO_LONG'), "a long groupname fails");

=end testing

=cut

sub validate_group_length {
    my $fm        = shift;
    my $groupName = shift;

    my $maxGroupNameLength = ($db->get('maxGroupNameLength')
			       ? $db->get('maxGroupNameLength')->prop('type')
			       : "") || 12;

    if ( length $groupName > $maxGroupNameLength ) {

        return $fm->localise('GROUP_TOO_LONG', 
			    {maxLength => $maxGroupNameLength});
    }
    else {
        return ('OK');
    }
}


=head2 validate_group_has_members FM MEMBERS

Validates that the cgi parameter MEMBERS is an array with at least one entry
Returns OK if true. Otherwise, returns NO_MEMBERS


=begin testing

ok(validate_group_has_members('',qw(foo bar)) eq 'OK', "We do ok with a group with two members");

ok(validate_group_has_members('',qw()) eq 'NO_MEMBERS', "We do ok with a group with no members"); 
ok(validate_group_has_members('')  eq 'NO_MEMBERS', "We do ok with a group with undef members");

=end testing

=cut

sub validate_group_has_members {
    my $fm      = shift;
    my @members = (@_);
    my $count   = @members;
    if ( $count == 0 ) {
        return ('NO_MEMBERS');
    }
    else {
        return ('OK');
    }
}



=head1  UTILITY FUNCTIONS


=head2 print_group_members FM ACCT

Takes an FM object and the name of a group.
Prints out an unordered list of the group's members.

=cut

sub print_group_members {
    my $fm   = shift;
    my $q = $fm->cgi;
    my $acct = $q->param('groupName');

    print $q->Tr(
	$q->td({-class => "sme-noborders"},
	    $fm->localise('GROUP_HAS_MEMBERS'))),"\n";

    my @members = split ( /,/, $accounts->get($acct)->prop('Members') );
    my %names;
    foreach my $m (@members) {
        my $name;
        if ( $m eq 'admin' ) {
            $name = "Administrator";
        }
        else {
            $name =
              $accounts->get($m)->prop('FirstName') . " "
              . $accounts->get($m)->prop('LastName');
        }
	$names{$m} = $name;
    }

    print $q->Tr(
	$q->td({-class => "sme-noborders"},
	    $q->p($q->ul(
		$q->li({-type => 'disc'}, 
		    [map { "$_ (${names{$_}})" } @members]))))),"\n"; 

    return;
}

sub print_group_delete_desc
{
    my $fm = shift;
    my $q = $fm->cgi;
    my $acct = $q->param('groupName');
    print $q->Tr(
	$q->td({-class => "sme-noborders"},
	    $q->p($fm->localise('DELETE_DESCRIPTION', {group => $acct})),
	    $q->br)),"\n";
    return '';
}
	 
sub print_ibay_list {
    my $fm   = shift;
    my $q = $fm->cgi;
    my $acct = $q->param('groupName');

    my %names;
    foreach my $ibay ( $accounts->ibays ) {
        if ( $ibay->prop('Group') eq $acct ) {
	    $names{$ibay->key} = $ibay->prop('Name');
        }
    }

    if (%names) {
	print $q->Tr(
	    $q->td({-class => "sme-noborders"},
		$q->p($fm->localise('IBAYS_WILL_BE_CHANGED')),
		$q->ul(
		    $q->li({-type => 'disc'}, 
			[map { "$_ (${names{$_}})" } sort keys %names])))),"\n";
    }

    return;
}



=head2 build_cgi_params()

Builds a CGI query string, using various sensible
defaults and esmith::FormMagick's props_to_query_string() method.

=cut

sub build_cgi_params {
    my ( $fm, $group ) = @_;

    my %props = (
        page       => 0,
        page_stack => "",
        ".id"      => $fm->{cgi}->param('.id') || "",
        groupName  => $group,
    );

    return $fm->props_to_query_string( \%props );
}

=head2 genUsers MEMBERS

Takes a comma delimited list of users and returns a string of 
html checkboxes for all system users with the members of the group
in $fm->{cgi}->parm('groupName')checked.

=cut

sub genUsers () {
    my $fm      = shift;
    my $members = "";
    my $group = $fm->{'cgi'}->param('groupName');

    if ($accounts->get($group)) {	
      $members = $accounts->get($group)->prop('Members');
    }	
    my %members;
    foreach my $member ( split ( /,/, $members ) ) {
        $members{$member} = 1;
    }
    my @users = sort { $a->key() cmp $b->key() } $accounts->users();

    # include Administrator at beginning of list

    my $out = "<tr>\n        <td class=\"sme-noborders-label\">"
      . $fm->localise('GROUP_MEMBERS')
      . "</td>\n        <td>\n"
      . "          <table border='0' cellspacing='0' cellpadding='0'>\n"
      . "            <tr>\n"
      . "              <td><input type=\"checkbox\" name=\"groupMembers\"";
    if ( $members{'admin'} ) {
        $out .= "checked";
    }
    $out .= " value=\"admin\"></td>\n              <td>Administrator (admin)</td>\n            </tr>\n";
    foreach my $user (@users) {
        my $checked = "";
        if ( $members{ $user->key() } ) {
            $checked = "checked";
        }
        my $name;
        if ( $user eq 'admin' ) { $name = 'Administrator'; }
        else {
            $name = $user->prop('FirstName') . " " . $user->prop('LastName');
        }

        $out .="            <tr>\n"
             . "              <td><input type=\"checkbox\" name=\"groupMembers\" $checked value=\""
          . $user->key
          . "\"></td>\n              <td>$name (".$user->key.")</td>\n            </tr>\n";

    }

    $out .= "          </table>\n        </td>\n    </tr>\n";
    return $out;
}

=head2 clear_params

This method clears-out the parameters used in form submission so that they are
not inadvertenly picked-up where they should not be.

=cut

sub clear_params
{
    my $self = shift;
    my $q = $self->{cgi};

    $q->delete('groupMembers');
    $q->delete('groupDesc');
    $q->delete('groupName');
}
