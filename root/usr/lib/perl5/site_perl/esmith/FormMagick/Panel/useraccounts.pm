#!/usr/bin/perl -w 

#----------------------------------------------------------------------
# $Id: useraccounts.pm,v 1.108 2004/11/11 20:05:56 charlieb Exp $
#----------------------------------------------------------------------
# copyright (C) 1999-2006 Mitel Networks Corporation
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
package    esmith::FormMagick::Panel::useraccounts;

use strict;

use esmith::FormMagick;
use esmith::AccountsDB;
use esmith::ConfigDB;
use esmith::cgi;
use esmith::util;
use File::Basename;
use Exporter;
use Carp qw(verbose);

our @ISA = qw(esmith::FormMagick Exporter);

our @EXPORT = qw(
    print_user_table
    print_acctName_field
    print_groupMemberships_field
    print_page_description
    get_ldap_value
    username_clash
    pseudonym_clash
    checkMaxUsers
    handle_user_accounts
    modify_admin
    emailforward
    verifyPasswords
    lock_account
    remove_account
    reset_password
    check_password
    print_save_or_add_button
    get_pptp_value
    print_ipsec_client_section
    get_prop

    system_password_compare 
    system_valid_password 
    system_change_password
    system_check_password
    system_authenticate_password
);

our $VERSION = sprintf '%d.%03d', q$Revision: 1.108 $ =~ /: (\d+).(\d+)/;

our $accountdb = esmith::AccountsDB->open();
our $configdb = esmith::ConfigDB->open();

=pod 

=head1 NAME

esmith::FormMagick::Panels::useraccounts - useful panel functions

=head1 SYNOPSIS

use esmith::FormMagick::Panels::useraccount;

my $panel = esmith::FormMagick::Panel::useraccount->new();
$panel->display();

=head1 DESCRIPTION


=head2 new();

Exactly as for esmith::FormMagick

=begin testing

$ENV{ESMITH_ACCOUNT_DB} = "10e-smith-base/accounts.conf";
$ENV{ESMITH_CONFIG_DB} = "10e-smith-base/configuration.conf";

open DATA, "echo '<form></form>'|";
use_ok('esmith::FormMagick::Panel::useraccounts');
use vars qw($panel);
ok($panel = esmith::FormMagick::Panel::useraccounts->new(), 
"Create panel object");
close DATA;
isa_ok($panel, 'esmith::FormMagick::Panel::useraccounts');
$panel->{cgi} = CGI->new();
$panel->parse_xml();

{ package esmith::FormMagick::Panel::useraccounts;
our $accountdb;
::isa_ok($accountdb, 'esmith::AccountsDB');
}

=end testing

=cut

sub new {
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
$panel->print_user_table;
like($_STDOUT_, qr/bart/, "Found usernames in user table output");
like($_STDOUT_, qr/ff0000/, "Found red 'reset password' output");

=cut

sub print_user_table {
    my $self = shift;
    my $q = $self->{cgi};
    my $account = $self->localise('ACCOUNT');
    my $acctName = $self->localise('USER_NAME');

    my $modify  = $self->localise('MODIFY');
    my $resetpw = $self->localise('PASSWORD_RESET');
    my $lock    = $self->localise('LOCK_ACCOUNT');
    my $account_locked = $self->localise('ACCOUNT_LOCKED');
    my $remove  = $self->localise('REMOVE');

    my @users = $accountdb->get('admin');
    push @users, $accountdb->users();

    unless ( scalar @users )
    {
        print $q->Tr($q->td($self->localise('NO_USER_ACCOUNTS')));
        return "";
    }
    print "  <tr>\n    <td colspan=\"2\">\n      ";
    print $q->start_table ({-CLASS => "sme-border"}),"\n        ";
    print $q->Tr(
        esmith::cgi::genSmallCell($q, $self->localise($account),"header"),
        esmith::cgi::genSmallCell($q, $self->localise($acctName),"header"),
        esmith::cgi::genSmallCell($q, $self->localise('VPN_CLIENT_ACCESS'), "header"),
        esmith::cgi::genSmallCell($q, $self->localise('ACTION'),"header",4));

    my $scriptname = basename($0);
    my $index=0;

    foreach my $u (@users) {
        my $username = $u->key();
        my $first    = $u->prop('FirstName');
        my $last     = $u->prop('LastName');
        my $lockable = $u->prop('Lockable') || 'yes';
        my $removable = $u->prop('Removable') || 'yes';
        my $vpnaccess = $u->prop('VPNClientAccess') || 'no';
        $vpnaccess = $vpnaccess eq 'yes' ? $self->localise('YES') :
                                           $self->localise('NO');

        my $params = $self->build_user_cgi_params($username, $u->props());

        my $password_set = $u->prop('PasswordSet');

        my $pagenum = ($username eq "admin") ? $self->get_page_by_name('SystemPasswordDummy')
        : $self->get_page_by_name('CheckMaxUsersUnlock');

        # make normal links
        my $lock_url = ($password_set eq 'yes') ?
            qq(<a href="$scriptname?$params&Next=Next&wherenext=LockAccount">$lock</a>) :
            qq($account_locked);

        $lock_url = "" unless ($lockable eq "yes");

        my $where_next = ($username eq "admin") ? "ModifyAdmin" : "CreateModify";
        my $action1 = "<a href=\"$scriptname?page=0&page_stack=&acctName=$username&Next=Next&action=modify&wherenext=$where_next\">$modify</a>";

        my $action2 = "<a href=\"$scriptname?page=$pagenum&page_stack=&Next=Next&acctName=$username\">$resetpw</a>";

        unless ($password_set eq 'yes')
        {
            $action2 = "<span class='error-noborders'>" . $action2 . "</span>";
        }

        my $action3 = ($removable eq "yes") ? "<a href=\"$scriptname?$params&Next=Next&wherenext=RemoveAccount\">$remove</a>" : '';

        print $q->Tr(esmith::cgi::genSmallCell($q, $username,"normal"),"        ",
        esmith::cgi::genSmallCell($q, "$first $last","normal"),"        ",
        esmith::cgi::genSmallCell($q, $vpnaccess),
        esmith::cgi::genSmallCell($q, "$action1","normal"),"        ",
        esmith::cgi::genSmallCell($q, "$action2","normal"),"        ",
        esmith::cgi::genSmallCell($q, "$lock_url","normal"),"        ",
        esmith::cgi::genSmallCell($q, "$action3","normal"));
        
        $index++;
    }

    print qq(</table></td></tr>\n);

    return "";
}

=head2 print_acctName_field

This subroutine is used to generate the Account name field on the form in 
the case of "create user", or to make it a plain uneditable string in the case
of "modify user".

=begin testing

my $self = esmith::FormMagick::Panel::useraccounts->new();
$self->{cgi} = CGI->new("");
print_acctName_field($self);
like($_STDOUT_, qr/text.*acctName/, "print text field if acctName not set");
like($_STDOUT_, qr/create/, "action=create if acctName not set");
$self->{cgi}->param(-name => 'acctName', -value => 'foo');
$self->{cgi}->param(-name => 'action', -value => 'modify');
print_acctName_field($self);
like($_STDOUT_, qr/hidden.*acctName/, "print hidden field if acctName is set");
like($_STDOUT_, qr/modify/, "action=modify if acctName already set");

=end testing

=cut

sub print_acctName_field {
    my $self = shift;
    my $cgi = $self->{cgi};
    my $an = $cgi->param('acctName') || '';
    print qq(<tr><td class=\"sme-noborders-label\">) . $self->localise('ACCOUNT_NAME') . qq(</td>\n);
    my $action = $cgi->param('action') || '';
    if ( $action eq 'modify') {
        print qq(
            <td>$an 
            <input type="hidden" name="acctName" value="$an">
            <input type="hidden" name="action" value="modify">
            </td>
        );
        # if there's no CGI data, fill in the fields with the account db
        # data for this user
        my $rec = $accountdb->get($an);
        my $fn = $cgi->param('FirstName') ? 
            $cgi->param('FirstName') : 
            ($rec ? ($rec->prop('FirstName')) : ''); 
        my $ln = $cgi->param('LastName') ?
            $cgi->param('LastName') :
            ($rec ? ($rec->prop('LastName')) : ''); 
        my $dept = $cgi->param('Dept') ? 
            $cgi->param('Dept') :
            ($rec ? ($rec->prop('Dept')) : ''); 
        my $company = $cgi->param('Company') ?
            $cgi->param('Company') :
            ($rec ? ($rec->prop('Company')) : ''); 
        my $street = $cgi->param('Street') ?
            $cgi->param('Street') :
            ($rec ? ($rec->prop('Street')) : ''); 
        my $city = $cgi->param('City') ?
            $cgi->param('City') :
            ($rec ? ($rec->prop('City')) : ''); 
        my $phone = $cgi->param('Phone') ?
            $cgi->param('Phone') :
            ($rec ? ($rec->prop('Phone')) : ''); 
        my $emf = $cgi->param('EmailForward') ?
            $cgi->param('EmailForward') :
            ($rec ? ($rec->prop('EmailForward')) : 'local'); 
        my $fwd = $cgi->param('ForwardAddress') ? 
            $cgi->param('ForwardAddress') :
            ($rec ? ($rec->prop('ForwardAddress')) : '');
        my $pptp = $cgi->param('VPNClientAccess') ?
            $cgi->param('VPNClientAccess') : 
            ($rec ? ($rec->prop('VPNClientAccess')) : 'no');
        # now that we're down with the 411, let's set the values
        $cgi->param(-name=>'FirstName', -value=>$fn);
        $cgi->param(-name=>'LastName', -value=>$ln);
        $cgi->param(-name=>'Dept', -value=>$dept);
        $cgi->param(-name=>'Company', -value=>$company);
        $cgi->param(-name=>'Street', -value=>$street);
        $cgi->param(-name=>'City', -value=>$city);
        $cgi->param(-name=>'Phone', -value=>$phone);
        $cgi->param(-name=>'EmailForward', -value=>$emf);
        $cgi->param(-name=>'ForwardAddress', -value=>$fwd);
        $cgi->param(-name=>'VPNClientAccess', -value=>$pptp);
    } else {
        print qq(
            <td><input type="text" name="acctName" value="$an">
            <input type="hidden" name="action" value="create">
            </td>
        );
    }

    print qq(</tr>\n);
    return undef;

}

=head2 print_groupMemberships_field()

Builds a list of groups for the create/modify user screen.

=begin testing

my $self = esmith::FormMagick::Panel::useraccounts->new();
$self->{cgi} = CGI->new("");
$self->print_groupMemberships_field();
like($_STDOUT_, qr/simpsons/, "Found simpsons in group list");
like($_STDOUT_, qr/flanders/, "Found flanders in group list");
$self->{cgi}->param(-name => 'acctName', -value => 'rod');
$self->print_groupMemberships_field();
like($_STDOUT_, qr/checked value="flanders"/, "Checked flanders group for user rod");

=end testing

=cut

sub print_groupMemberships_field {
    my ($self) = @_;
    my $q = $self->{cgi};
    my $user = $q->param('acctName');

    if (my @groups = $accountdb->groups()) {

        print "<tr><td class=\"sme-noborders-label\">",
        $self->localise('GROUP_MEMBERSHIPS'),
        "</td><td>\n";

        print $q->start_table({-class => "sme-border"}),"\n";
        print $q->Tr(
            esmith::cgi::genSmallCell($q, $self->localise('MEMBER'),"header"),
            esmith::cgi::genSmallCell($q, $self->localise('GROUP'),"header"),
            esmith::cgi::genSmallCell($q, $self->localise('DESCRIPTION'),"header")
        );

        foreach my $g (@groups) {
            my $groupname = $g->key();
            my $checked;
            if ($user and $accountdb->is_user_in_group($user, $groupname)) {
                $checked = 'checked';
            } else {
                $checked = '';
            }

            print $q->Tr(
                $q->td(
                    "<input type=\"checkbox\""
                    . " name=\"groupMemberships\""
                    . " $checked value=\"$groupname\">"
                ),
                esmith::cgi::genSmallCell($q, $groupname,"normal"),
                esmith::cgi::genSmallCell( $q, $accountdb->get($groupname)->prop("Description"),"normal")
            );
        }

        print "</table></td></tr>\n";

    }

    return undef;

}

=head2 print_page_description($self, "reset|lock|remove")

Generates the page description for the the somewhat similar Reset
Password, Lock Account and Remove Account pages.

=begin testing

my $self = esmith::FormMagick::Panel::useraccounts->new();
$self->{cgi} = CGI->new({ acctName => 'bart' });
print_page_description($self, "reset");
like($_STDOUT_, qr/bart/, "print_page_description prints username");
like($_STDOUT_, qr/Bart Simpson/, "print_page_description prints name");
like($_STDOUT_, qr/RESET_DESC/, "print_page_description prints description");

=end testing

=cut

sub print_page_description {
    my ($self, $pagename) = @_;
    unless (grep /^$pagename$/, qw(reset lock remove)) {
        warn "Can't generate page description for invalid pagename $pagename\n";
        return;
    }

    $pagename = uc($pagename);

    my $desc          = $self->localise("${pagename}_DESC");
    my $desc2         = $self->localise("${pagename}_DESC2");

    my $acctName      = $self->{cgi}->param('acctName');
    my $name          = $accountdb->get($acctName)->prop('FirstName') . " "
    . $accountdb->get($acctName)->prop('LastName');

    print qq{
        <tr><td colspan="2">
        <p>$desc "$acctName" ($name)</p>
        $desc2
        <input type="hidden" name="acctName" value="$acctName">
        </td></tr>
    };

    return;
}

=head1 ROUTINES FOR FILLING IN FIELD DEFAULT VALUES

=head2 get_ldap_value($field)

This subroutine generates the default field value on the form using the
parameter specified.

In this case, the default field values come from LDAP/directory
settings.

If a CGI parameter has been passed that contains an account name, we
assume that a value has already been set, as we're modifying a user, and
use that value instead of a default.

=for testing
my $self = esmith::FormMagick::Panel::useraccounts->new();
$self->{cgi} = CGI->new("");
is(get_ldap_value($self, "Dept"), "Main", "Pick up default value from LDAP");
$self->{cgi} = CGI->new({ acctName => 'bart' });
is(get_ldap_value($self, "Dept"), undef, "Don't pick up LDAP data if username provided");

=cut

sub get_ldap_value {
    my ($self, $field) = @_;

    # don't do the lookup if this is a modification of an existing user
    if ($self->{cgi}->param('acctName')) {
        return $self->{cgi}->param($field);
    }

    my %CGIParam2DBfield = (
        Dept    => 'defaultDepartment',
        Company => 'defaultCompany',
        Street  => 'defaultStreet',
        City    => 'defaultCity',
        Phone   => 'defaultPhoneNumber'
    );

    return $configdb->get('ldap')->prop($CGIParam2DBfield{$field});
}

sub get_pptp_value
{
    return $configdb->get('pptpd')->prop('AccessDefault') || 'no';
}

=head1 VALIDATION ROUTINES

=head2 pseudonym_clash

Validation routine to check whether a the first/last names clash with
existing pseudonyms.

Note that it won't be considered a "clash" if there is an existing
pseudonym which belongs to the same user -- it's only a clash if the
generated pseudonyms are the same but the usernames aren't.

=begin testing

my $self = esmith::FormMagick::Panel::useraccounts->new();

$self->{cgi} = CGI->new({ 
    acctName => 'skud', 
    FirstName => 'Kirrily',
    LastName => 'Robert' 
});

is  (pseudonym_clash($self, 'Kirrily'), "OK", "New name doesn't clash pseudonyms");

$self->{cgi} = CGI->new({ 
    acctName => 'bart2', 
    FirstName => 'Bart',
    LastName => 'Simpson' 
});

isnt(pseudonym_clash($self, 'Bart'), "OK", "Existing pseudonym with non-matching username causes clash");

$self->{cgi} = CGI->new({ 
    acctName => 'bart', 
    FirstName => 'Bart',
    LastName => 'Simpson' 
});

is(pseudonym_clash($self, 'Bart'), "OK", "Existing pseudonym with matching username shouldn't clash");

=end testing

=cut

sub pseudonym_clash {
    my ($self, $first) = @_;
    $first ||= "";
    my $last     = $self->{cgi}->param('LastName') || "";
    my $acctName = $self->{cgi}->param('acctName') || "";

    my $up = "$first $last";

    $up =~ s/^\s+//;    
    $up =~ s/\s+$//;   
    $up =~ s/\s+/ /g; 
    $up =~ s/\s/_/g;         

    my $dp = $up; 
    $dp =~ s/_/./g;

    $dp = $accountdb->get($dp);
    $up = $accountdb->get($up);

    my $da = $dp->prop('Account') if $dp;
    my $ua = $up->prop('Account') if $up;
    if ($dp and $da and $da ne $acctName) 
    {
        return $self->localise('PSEUDONYM_CLASH', 
        { 
            acctName => $acctName, 
            clashName => $da,
            pseudonym => $dp->key
        });
    } 
    elsif ($up and $ua and $ua ne $acctName) 
    {
        return $self->localise('PSEUDONYM_CLASH', 
        { 
            acctName => $acctName, 
            clashName => $ua,
            pseudonym => $up->key
        });
    }
    else 
    {
        return "OK";
    }
}

=head2 emailforward()

Validation routine for email forwarding

=cut

sub emailforward {
    my ($self, $data) = @_;
    my $response = $self->email_simple($data);
    if ($response eq "OK")
    {
        return "OK";
    }
    elsif ($data eq "")
    {
        # Blank is ok, only if we're not forwarding, which means that the
        # EmailForward param must be set to 'local'.
        my $email_forward = $self->{cgi}->param('EmailForward') || '';
        $email_forward =~ s/^\s+|\s+$//g;
        return 'OK' if $email_forward eq 'local';
        return $self->localise('CANNOT_CONTAIN_WHITESPACE');
    }
    else
    {
        return $self->localise('CANNOT_CONTAIN_WHITESPACE')
            if ( $data =~ /\s+/ );
        # Permit a local address.
        return "OK" if $data =~ /^[a-zA-Z][a-zA-Z0-9\._\-]*$/;
        return $self->localise('UNACCEPTABLE_CHARS');
    }
}

=head2 verifyPasswords()

Returns an error message if the two new passwords input don't match.

=cut

sub verifyPasswords {
    my $self = shift;
    my $pass2 = shift;

    my $pass1 = $self->{cgi}->param('password1');
    unless ($pass1 eq $pass2) {
        $self->{cgi}->param( -name => 'wherenext', -value => 'Password' );
        return "PASSWORD_VERIFY_ERROR";
    }
    return "OK";
}

=head1 CREATING AND MODIFYING USERS

=head2 checkMaxUsers()

Returns an error message if the current number of users is greater than or
equal to the sysconfig|MaxUsers property.

Takes the name of the next page to go to if the test succeeds as an argument.

=cut

sub checkMaxUsers
{
    my ($self, $next_page) = @_;

    # Get value of MaxUsers if it exists.
    my $sysconfig = $configdb->get('sysconfig');
    my $maxUsers = (($sysconfig) ? $sysconfig->prop('MaxUsers') : '') || '';
    my $activeUsers = scalar $accountdb->activeUsers() || 0;
    if ((defined $activeUsers and $maxUsers ne '') 
        and ($activeUsers >= $maxUsers))
    {
        $self->error('MAX_USERS_EXCEEDED');
    }
    else
    {
        $self->{cgi}->param(-name => 'wherenext', -value => $next_page);
    }
}

=head2 handle_user_accounts()

This is the routine called by the "Save" button on the create/modify page.  
It checks the "action" param and calls either create_user() or modify_user()
as appropriate.

=cut

sub handle_user_accounts {
    my ($self) = @_;

    my $cgi = $self->{cgi};

    if ($cgi->param("action") eq "create") {
        my $msg = create_user($self);
        if ($msg eq 'USER_CREATED')
        {
            $self->success($msg);
        }
        else
        {
            $self->error($msg);
        }
    }
    else {
        modify_user($self);
        $self->success('USER_MODIFIED');
    }
}

=head2 print_save_or_add_button()

=cut

sub print_save_or_add_button {

    my ($self) = @_;

    my $cgi = $self->{cgi};

    if (($cgi->param("action") || '') eq "modify") {
        $self->print_button("SAVE");
    } else {
        $self->print_button("ADD");
    }

}

=head2 modify_admin($self)

=cut

sub modify_admin
{
    my ($self) = @_;

    my $acct = $accountdb->get('admin');

    my %newProperties = (
        'FirstName'      => $self->{cgi}->param('FirstName'),
        'LastName'       => $self->{cgi}->param('LastName'),
        'EmailForward'   => $self->{cgi}->param('EmailForward'),
        'ForwardAddress' => $self->{cgi}->param('ForwardAddress'),
        'VPNClientAccess'=> $self->{cgi}->param('VPNClientAccess'),
    );

    $acct->merge_props(%newProperties);

    undef $accountdb;

    my $status = 
        system ("/sbin/e-smith/signal-event", "user-modify-admin", 'admin');

    $accountdb = esmith::AccountsDB->open();

    if ($status == 0)
    {
        $self->success('USER_MODIFIED', 'First');
    }
    else
    {
        $self->error('CANNOT_MODIFY_USER', 'First');
    }
    return;
}

=head2 modify_user($self)

=cut

sub modify_user {
    my ($self) = @_;
    my $acctName = $self->{cgi}->param('acctName');

    unless (($acctName) = ($acctName =~ /^(\w[\-\w_\.]*)$/)) {
        return $self->error($self->localise('TAINTED_USER',
            { acctName => $acctName }));
    }
    # Untaint the username before use in system()
    $acctName = $1;

    my $acct = $accountdb->get($acctName);
    my $acctType = $acct->prop('type');

    if ($acctType eq "user")
    {
        $accountdb->remove_user_auto_pseudonyms($acctName);
        my %newProperties = (
            'FirstName'      => $self->{cgi}->param('FirstName'),
            'LastName'       => $self->{cgi}->param('LastName'),
            'Phone'          => $self->{cgi}->param('Phone'),
            'Company'        => $self->{cgi}->param('Company'),
            'Dept'           => $self->{cgi}->param('Dept'),
            'City'           => $self->{cgi}->param('City'),
            'Street'         => $self->{cgi}->param('Street'),
            'EmailForward'   => $self->{cgi}->param('EmailForward'),
            'ForwardAddress' => $self->{cgi}->param('ForwardAddress'),
            'VPNClientAccess'=> $self->{cgi}->param('VPNClientAccess'),
        );

        $acct->merge_props(%newProperties);

        $accountdb->create_user_auto_pseudonyms($acctName);

        my @old_groups = $accountdb->user_group_list($acctName);
        my @new_groups = $self->{cgi}->param("groupMemberships");
        $accountdb->remove_user_from_groups($acctName, @old_groups);
        $accountdb->add_user_to_groups($acctName, @new_groups);

        undef $accountdb;

        unless (system ("/sbin/e-smith/signal-event", "user-modify", 
                        $acctName) == 0) {
            $accountdb = esmith::AccountsDB->open();
            return $self->error('CANNOT_MODIFY_USER');
        }
        $accountdb = esmith::AccountsDB->open();
    }
    $self->success('USER_MODIFIED');
}

=head2 create_user

Adds a user to the accounts db.

=cut

sub create_user {
    my $self = shift;
    my $q = $self->{cgi};

    my $acctName = $q->param('acctName');

    my $msg = $self->validate_acctName($acctName);
    unless ($msg eq "OK")
    {
        return $msg;
    }

    $msg = $self->validate_acctName_length($acctName);
    unless ($msg eq "OK")
    {
        return $msg;
    }

    $msg = $self->validate_acctName_conflict($acctName);
    unless ($msg eq "OK")
    {
        return $msg;
    }

    my %userprops;
    foreach my $field ( qw( FirstName LastName Phone Company Dept
        City Street EmailForward ForwardAddress VPNClientAccess) )
    {
        $userprops{$field} = $q->param($field);
    }
    $userprops{'PasswordSet'} = "no";
    $userprops{'type'}        = 'user';

    my $acct = $accountdb->new_record($acctName)
        or warn "Can't create new account for $acctName (does it already exist?)\n";
    $acct->reset_props(%userprops);
    $accountdb->create_user_auto_pseudonyms($acctName);
    my @groups = $self->{cgi}->param("groupMemberships");
    $accountdb->add_user_to_groups($acctName, @groups);

    undef $accountdb;

    # Untaint the username before use in system()
    $acctName =~ /^(\w[\-\w_\.]*)$/;
    $acctName = $1;

    if (system ("/sbin/e-smith/signal-event", "user-create", $acctName)) 
    {
        $accountdb = esmith::AccountsDB->open();
        return $self->localise("ERR_OCCURRED_CREATING");
    }

    $accountdb = esmith::AccountsDB->open();

    $self->set_groups();
    return 'USER_CREATED';
}

=head2 set_groups

Sets a user's groups in the accounts db.  This is called as part of the 
create_user() routine.

=cut

sub set_groups 
{
    my $self = shift;
    my $q = $self->{cgi};
    my $acctName = $q->param('acctName');

    my @groups = $q->param('groupMemberships');
    $accountdb->set_user_groups($acctName, @groups);

}

=head1 REMOVING ACCOUNTS

=head2 remove_account()

=cut

sub remove_account {
    my ($self) = @_;
    my $acctName = $self->{cgi}->param('acctName');

    my $acct = $accountdb->get($acctName);
    if ($acct->prop('type') eq "user") {
        $acct->set_prop('type', "user-deleted");

        undef $accountdb;

	# Untaint the username before use in system()
	$acctName =~ /^(\w[\-\w_\.]*)$/;
	$acctName = $1;
        if (system ("/sbin/e-smith/signal-event", "user-delete", $acctName))
        {
            $accountdb = esmith::AccountsDB->open();
            return $self->error("ERR_OCCURRED_DELETING");
        }

        $accountdb = esmith::AccountsDB->open();
        $accountdb->get($acctName)->delete;

    } else {
        # FIXME - this should be handled by input validation
        # XXX error message here
    }
    $self->{cgi}->param(-name => 'wherenext', -value => 'First');
}

=head1 RESETTING THE PASSWORD

=head2 reset_password()

=cut

sub reset_password {
    my ($self) = @_;
    my $acctName = $self->{cgi}->param('acctName');

    unless (($acctName) = ($acctName =~ /^(\w[\-\w_\.]*)$/)) {
        return $self->error('TAINTED_USER');
    }
    $acctName = $1;

    my $acct = $accountdb->get($acctName);

    if ( $acct->prop('type') eq "user")
    {
        esmith::util::setUserPassword ($acctName,
            $self->{cgi}->param('password1'));

        $acct->set_prop("PasswordSet", "yes");
        undef $accountdb;

        if (system("/sbin/e-smith/signal-event", "password-modify", $acctName))
        {
            $accountdb = esmith::AccountsDB->open();
            $self->error("ERR_OCCURRED_MODIFYING_PASSWORD");
        }
        $accountdb = esmith::AccountsDB->open();

        $self->success($self->localise('PASSWORD_CHANGE_SUCCEEDED',
            { acctName => $acctName}));
    }
    else
    {
        $self->error($self->localise('NO_SUCH_USER',
            { acctName => $acctName}));
    }
}

=head1 LOCKING AN ACCOUNT

=head2 lock_account()

=cut

sub lock_account {
    my ($self) = @_;
    my $acctName = $self->{cgi}->param('acctName');
    my $acct = $accountdb->get($acctName);
    if ($acct->prop('type') eq "user")
    {
        undef $accountdb;

	# Untaint the username before use in system()
	$acctName =~ /^(\w[\-\w_\.]*)$/;
	$acctName = $1;
        if (system("/sbin/e-smith/signal-event", "user-lock", $acctName))
        {
            $accountdb = esmith::AccountsDB->open();
            return $self->error("ERR_OCCURRED_LOCKING");
        }

        $accountdb = esmith::AccountsDB->open();

        $self->success($self->localise('LOCKED_ACCOUNT',
            { acctName => $acctName}));
    }
    else
    {
        $self->error($self->localise('NO_SUCH_USER',
            { acctName => $acctName}));
    }
}


=head1 MISCELLANEOUS ROUTINES

=head2 build_user_cgi_params()

Builds a CGI query string based on user data, using various sensible 
defaults and esmith::FormMagick's props_to_query_string() method.

=cut

sub build_user_cgi_params {
    my ($self, $acctName, %oldprops) = @_;

    my %props = (
        page    => 0,
        page_stack => "",
        ".id"         => $self->{cgi}->param('.id') || "",
        acctName    => $acctName,
        #%oldprops
    );

    return $self->props_to_query_string(\%props);
}

=pod

=head2 validate_acctName

Checks that the name supplied does not contain any unacceptable chars.
Returns OK on success or a localised error message otherwise.

=for testing
is($panel->validate_acctName('foo'), 'OK', 'validate_acctName');
isnt($panel->validate_acctName('3amigos'), 'OK', ' .. cannot start with number');
isnt($panel->validate_acctName('betty ford'), 'OK', ' .. cannot contain space');

=cut

sub validate_acctName
{
    my ($self, $acctName) = @_;

    unless ($accountdb->validate_account_name($acctName))
    {
        return $self->localise('ACCT_NAME_HAS_INVALID_CHARS',
        {acctName => $acctName});
    }
    return "OK";
}

=head2 validate_account_length FM ACCOUNTNAME

returns 'OK' if the account name is shorter than the maximum account name length
returns 'ACCOUNT_TOO_LONG' otherwise

=begin testing

ok(($panel->validate_acctName_length('foo') eq 'OK'), "a short account name passes");
ok(($panel->validate_acctName_length('fooooooooooooooooo') eq 'ACCOUNT_TOO_LONG'), "a long account name fails");

=end testing

=cut

sub validate_acctName_length {
    my $self        = shift;
    my $acctName = shift;


    my $maxAcctNameLength = ($configdb->get('maxAcctNameLength') 
        ? $configdb->get('maxAcctNameLength')->prop('type')
        : "") || 12;

    if ( length $acctName > $maxAcctNameLength ) {

        return $self->localise('ACCOUNT_TOO_LONG', 
            {maxLength => $maxAcctNameLength});
    }
    else {
        return ('OK');
    }
}

=head2 validate_acctName_conflict

Returns 'OK' if the account name doesn't yet exist.  Returns a localised error
otherwise.

=cut

sub validate_acctName_conflict
{
    my $self        = shift;
    my $acctName = shift;

    my $account = $accountdb->get($acctName);
    my $type;

    if (defined $account)
    {
        $type = $account->prop('type');
    }
    elsif (defined getpwnam($acctName) || defined getgrnam($acctName))
    {
        $type = "system";
    }
    else
    {
        return('OK');
    }
    return $self->localise('ACCOUNT_CONFLICT',
    { account => $acctName, 
      type => $type,
});
}

=head2 check_password

Validates the password using the desired strength

=cut

sub check_password {
    my $self = shift;
    my $pass1 = shift;

    my $check_type;
    my $rec = $configdb->get('passwordstrength');
    $check_type = ($rec ? ($rec->prop('Users') || 'none') : 'none');

    return $self->validate_password($check_type,$pass1);
}


=head2 get_prop ITEM PROP

A simple accessor for esmith::ConfigDB::Record::prop

=cut

sub get_prop
{
    my ($fm, $item, $prop, $default) = @_;

    return $configdb->get_prop($item, $prop) || $default;
}


=head1 System Password manipulation routines

XXX FIXME - These should be merged with the useraccouts versions

=head2 system_password_compare

=cut

sub system_password_compare
{
    my $self = shift;
    my $pass2 = shift;

    my $pass1 = $self->{cgi}->param('pass');
    unless ($pass1 eq $pass2) {
        $self->{cgi}->param( -name => 'wherenext', -value => 'Password' );
        return "SYSTEM_PASSWORD_VERIFY_ERROR";
    }
    return "OK";
}

=head2 system_valid_password

Throw an error if the password doesn't consist solely of one or more printable characters.

=cut

sub system_valid_password
{
    my $self = shift;
    my $pass1 = shift;
    # If the password contains one or more printable character
    if ($pass1 =~ /^([ -~]+)$/) {
        return('OK');
    } else {
        $self->{cgi}->param( -name => 'wherenext', -value => 'Password' );
        return 'SYSTEM_PASSWORD_UNPRINTABLES_IN_PASS';
    }
}

=head2 system_check_password

Validates the password using the desired strength

=cut

sub system_check_password
{
    my $self = shift;
    my $pass1 = shift;

    use esmith::ConfigDB;
    my $conf = esmith::ConfigDB->open();
    my $check_type;
    my $rec;
    if ($conf)
    {
        $rec = $conf->get('passwordstrength');
    }
    $check_type = ($rec ? ($rec->prop('Admin') || 'strong') : 'strong');

    return $self->validate_password($check_type,$pass1);
}

=head2 authenticate_password

Compares the password with the current system password

=cut

sub system_authenticate_password
{
    my $self = shift;
    my $pass = shift;

    if (esmith::util::authenticateUnixPassword( ($configdb->get_value("AdminIsNotRoot") eq 'enabled') ? 'admin' : 'root', $pass))
    {
        return "OK";
    }
    else
    {
        return "SYSTEM_PASSWORD_AUTH_ERROR";
    }
}

=head2 system_change_password

If everything has been validated, properly, go ahead and set the new password.

=cut

sub system_change_password
{
    my ($self) = @_;
    my $pass = $self->{cgi}->param('pass');

    ($configdb->get_value("AdminIsNotRoot") eq 'enabled') ? esmith::util::setUnixPassword('admin',$pass) : esmith::util::setUnixSystemPassword($pass);
    esmith::util::setServerSystemPassword($pass);

    my $result = system("/sbin/e-smith/signal-event password-modify admin");

    if ($result == 0)
    {
        $self->success('SYSTEM_PASSWORD_CHANGED', 'First');
    }
    else
    {
        $self->error("Error occurred while modifying password for admin.", 'First');
    }

    return;
}

sub print_ipsec_client_section
{
    my $self = shift;
    my $q = $self->cgi;

    # Don't show ipsecrw setting unless the status property exists
    return '' unless ($configdb->get('ipsec') 
        && $configdb->get('ipsec')->prop('RoadWarriorStatus'));
    # Don't show ipsecrw setting unless /sbin/e-smith/roadwarrior exists
    return '' unless -x '/sbin/e-smith/roadwarrior';
    my $acct = $q->param('acctName');
    my $rec = $accountdb->get($acct) if $acct;
    if ($acct and $rec)
    { 
        my $pwset = $rec->prop('PasswordSet') || 'no';
        my $VPNaccess = $rec->prop('VPNClientAccess') || 'no';
        if ($pwset eq 'yes' and $VPNaccess eq 'yes')
        {
            print $q->Tr(
                $q->td({-class=>'sme-noborders-label'}, 
                $self->localise('LABEL_IPSECRW_DOWNLOAD')),
                $q->td({-class=>'sme-noborders-content'},
                $q->a({-class=>'button-like', 
                -href=>"?action=getCert&user=$acct"}, 
                $self->localise('DOWNLOAD'))));
        }
    }
    return '';
}

sub get_ipsec_client_cert
{
    my $self = shift;
    my $q = shift;
    my $user = $q->param('user');
    ($user) = ($user =~ /^(.*)$/);

    die "Invalid user: $user\n" unless getpwnam($user);

    open (KID, "/sbin/e-smith/roadwarrior get_client_cert $user |")
        or die "Can't fork: $!";
    my $certfile = <KID>;
    close KID;

    require File::Basename;
    my $certname = File::Basename::basename($certfile);

    print "Expires: 0\n";
    print "Content-type: application/x-pkcs12\n";
    print "Content-disposition: inline; filename=$certname\n";
    print "\n";

    open (CERT, "<$certfile");
    while (<CERT>)
    {
        print;
    }
    close CERT;

    return '';
}

sub display_email_forwarding
{
    return defined $configdb->get('smtpd');
}

1;
