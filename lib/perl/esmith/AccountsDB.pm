#----------------------------------------------------------------------
# Copyright 1999-2003 Mitel Networks Corporation
# This program is free software; you can redistribute it and/or
# modify it under the same terms as Perl itself.
#----------------------------------------------------------------------

package esmith::AccountsDB;

use strict;
use warnings;
use esmith::db;

use vars qw( $AUTOLOAD @ISA );

use esmith::DB::db;
@ISA = qw(esmith::DB::db);

=head1 NAME

esmith::AccountsDB - interface to esmith configuration database

=head1 SYNOPSIS

    use esmith::AccountsDB;
    my $a = esmith::AccountsDB->open;

    my @users    = $a->users();
    my @groups   = $a->groups();
    my @ibays    = $a->ibays(); 
    my @printers = $a->printers();
    my @pseudonyms = $a->pseudonyms();

    $a->is_user_in_group($user, $group);
    my @groups = $a->user_group_list($user);
    $a->add_user_to_groups($user, @groups);
    $a->remove_user_from_groups($user, @groups);

    $a->create_user_auto_pseudonyms($user);
    $a->remove_user_auto_pseudonyms($user);
    $a->remove_all_user_pseudonyms($user);
    my $dp = $a->dot_pseudonym($user);
    my $up = $a->underbar_pseudonym($user);
    my $uid = $a->get_next_uid();

=head1 DESCRIPTION

This module provides an abstracted interface to the esmith accounts
database.

=cut

our $VERSION = sprintf '%d.%03d', q$Revision: 1.18 $ =~ /: (\d+).(\d+)/;

=head2 open()

Loads an existing account database and returns an esmith::AccountsDB
object representing it.

=begin testing

use esmith::TestUtils qw(scratch_copy);

use_ok("esmith::AccountsDB");
use esmith::db;
use vars qw($a);

my $conf = scratch_copy('10e-smith-lib/accounts.conf');
$a = esmith::AccountsDB->open($conf);
isa_ok($a, 'esmith::AccountsDB');
is( $a->get("global")->prop('type'), "system", "We can get stuff from the db");

=end testing

=cut

sub open {
    my($class, $file) = @_;
    $file = $file || $ENV{ESMITH_ACCOUNT_DB} || "accounts";
    return $class->SUPER::open($file);
}

=head2 open_ro()

Like esmith::DB->open_ro, but if given no $file it will try to open the
file in the ESMITH_ACCOUNT_DB environment variable or accounts.

=begin testing

=end testing

=cut

sub open_ro {
    my($class, $file) = @_;
    $file = $file || $ENV{ESMITH_ACCOUNT_DB} || "accounts";
    return $class->SUPER::open_ro($file);
}

=head2 users(), groups(), ibays(), printers(), pseudonyms()

Returns a list of records (esmith::DB::db::Record objects) of the 
given type.

=for testing
foreach my $t (qw(users groups pseudonyms)) {
    my @list = $a->$t();
    ok(@list, "Got a list of $t");
    isa_ok($list[0], 'esmith::DB::db::Record');
}

=cut

sub AUTOLOAD {
    my $self = shift;
    my ($called_sub_name) = ($AUTOLOAD =~ m/([^:]*)$/);
    my @types = qw( users groups ibays printers pseudonyms);
    if (grep /^$called_sub_name$/, @types) {
        $called_sub_name =~ s/s$//g;    # de-pluralize
        return $self->get_all_by_prop(type => $called_sub_name);
    }
}

=head1 GROUP MANAGEMENT

=head2 $a->is_user_in_group($user, $group)

Returns true if the user is a member of the group, false otherwise.  The
arguments are a user name and a group name.

This routine will return undef if there is no such group, false (but
defined) if the user is not in the group, and true if the user is in the
group.

=for testing
ok($a->is_user_in_group('bart', 'simpsons'), "Bart is in group Simpsons");
ok(not($a->is_user_in_group('moe', 'simpsons')), "Moe is not in group Simpsons");
ok(not(defined $a->is_user_in_group('moe', 'flanders')), "No such group as Flanders");

=cut

sub is_user_in_group {
    my ($self, $user, $group) = @_;
    $group = $self->get($group) || return undef;
    my $members = $group->prop('Members');

    return grep(/^$user$/, split /,/, $members) ? 1 : 0;
}

=head2 $a->user_group_list($user)

Get a list of groups (by name) of which a user is a member.  The $user argument 
is simply the username.

=for testing
my @groups = $a->user_group_list('bart');
is_deeply(\@groups, ['simpsons'], "Bart's group list is 'simpsons'");

=cut

sub user_group_list {
    my ($self, $user) = @_;
    my @groups = $self->groups();
    my @user_groups;
    foreach my $g (@groups) {
        push(@user_groups, $g->key()) 
            if $self->is_user_in_group($user, $g->key());
    }
    return @user_groups;
}

=head2 $a->add_user_to_groups($user, @groups)

Given a list of groups (by name), adds the user to all of them.

Doesn't signal the group-modify event, just does the DB work.

Note: the method used here is a bit kludgy.  It could result in a user 
being in the same group twice.

=for testing
my @groups = $a->groups();
$a->remove_user_from_groups('maggie', map { $_->key() } @groups);
my @mg = $a->user_group_list('maggie');
is(scalar @mg, 0, "Maggie has been removed from all groups");
$a->add_user_to_groups('maggie', 'simpsons');
@mg = $a->user_group_list('maggie');
is_deeply(\@mg, ['simpsons'], "Maggie has been added to group 'simpsons'");
$a->remove_user_from_groups('maggie', 'simpsons');
@mg = $a->user_group_list('maggie');
is_deeply(\@mg, [], "Maggie's been removed from all groups again");
$a->set_user_groups('maggie', 'simpsons');
@mg = $a->user_group_list('maggie');
is_deeply(\@mg, ['simpsons'], "Maggie's groups have been set to: 'simpsons'");

=cut

sub add_user_to_groups {
    my ($self, $user, @groups) = @_;
    GROUP: foreach my $group (@groups) {
	unless (($group) = ($group =~ /(^[\w.-]+$)/))
	{
            warn "Group name doesn't look like a group!\n";
            next GROUP;
        }

        my $group_rec = $self->get($group) || next GROUP;
        my @members = split(/,/, $group_rec->prop('Members'));
        push @members, $user;
        # Remove duplicates
        my %members = map { $_ => 1 } @members;
        $group_rec->set_prop('Members', join(',', sort keys %members));
    }
}

=head2 $a->remove_user_from_groups($user, @groups)

Given a list of groups, removes a user from all of them.
Doesn't signal the group-modify event, just does the DB work.

=cut

sub remove_user_from_groups {
    my ($self, $user, @groups) = @_;

    GROUP: foreach my $g (@groups) {
        my $group_rec = $self->get($g) || next GROUP;
        my $members = $group_rec->prop('Members');
        my @members = split (/,/, $members);
        @members = grep (!/^$user$/, @members);
        @members = qw(admin) unless @members; # admin *must* be in every group
        $group_rec->set_prop('Members', join(',', @members));
    }
}

=head2 $a->set_user_groups($user, @groups)

Sets the user's groups in one fell swoop.  Under the hood, it's removing
the user from every group they're in then adding them to the set you give.

=cut

sub set_user_groups
{
    my ($self, $user, @groups) = @_;
    my @old_groups = $self->user_groups_list($user);
    $self->remove_user_from_groups($user, @old_groups);
    $self->add_user_to_groups($user, @groups);
}

=head1 PSEUDONYM MANAGEMENT

=head2 $a->create_user_auto_pseudonyms($user)

Given a user name, creates standard pseudonyms ("dot" and "underbar" style)
for that user.

=for testing
my $user = 'bart';
ok($a->pseudonyms(), "There are pseudonyms in the accounts db");
$a->remove_user_auto_pseudonyms($user);
ok(! $a->get('bart.simpson'), "Removed dot-pseudonym");
ok(! $a->get('bart_simpson'), "Removed underbar-pseudonym");
$a->create_user_auto_pseudonyms($user);
ok($a->get('bart.simpson'), "Created dot-pseudonym");
ok($a->get('bart_simpson'), "Created underbar-pseudonym");

=cut

sub create_user_auto_pseudonyms {
    my ($self, $user) = @_;
    my $user_rec = $self->get($user);
    my $firstName = $user_rec->prop("FirstName");
    my $lastName  = $user_rec->prop("LastName");

    my $dot_pseudonym = dot_pseudonym($self, $user);
    my $underbar_pseudonym = underbar_pseudonym($self, $user);

    my $dot_acct = $self->get($dot_pseudonym) ||
    $self->new_record($dot_pseudonym,      { type => 'pseudonym', 
                                             Account => $user} );

    my $underbar_acct = $self->get($underbar_pseudonym) ||
    $self->new_record($underbar_pseudonym, { type => 'pseudonym', 
                                             Account => $user} );
}


=head2 $a->remove_all_user_pseudonyms($user)

Given a username, remove any pseudonyms related to that user from the
accounts database. Also removes any pseudonyms related to a pseudonym
being removed. Returns the number of pseudonym records deleted.

=cut

sub remove_all_user_pseudonyms {
    my ($self, $user) = @_;
    my $count = 0;
    foreach my $p_rec (grep { $_->prop("Account") eq $user } $self->pseudonyms())
    {
	foreach my $p_p_rec (grep { $_->prop("Account") eq $p_rec->key } $self->pseudonyms())
	{
	    $p_p_rec->delete;
	    $count++;
	}
	$p_rec->delete;
	$count++;
    }
    return $count;
}

=head2 $a->remove_user_auto_pseudonyms($user)

Given a username, remove the dot_pseudonym and underbar_pseudonym
related to that user from the accounts database.  Returns the number
of pseudonym records deleted.

=cut

sub remove_user_auto_pseudonyms {
    my ($self, $user) = @_;
    my $dot_pseudonym = dot_pseudonym($self, $user);
    my $underbar_pseudonym = underbar_pseudonym($self, $user);
    my $count = 0;
    foreach my $p_rec ($self->get($dot_pseudonym),
			           $self->get($underbar_pseudonym))
    {
        if (defined $p_rec && $p_rec->prop("type") eq "pseudonym" &&
			      $p_rec->prop("Account") eq $user)
        {
            $p_rec->delete;
            $count++;
        }
    }
    return $count;
}

=head2 $a->dot_pseudonym($user)

Returns the "dot"-style pseudonym for a user as a string.  For instance, 
dot_pseudonym("bart") might return "bart.simpson".

=cut

sub dot_pseudonym {
    my ($self, $user) = @_;
    my $user_rec = $self->get($user);
    my $firstName = $user_rec->prop("FirstName");
    my $lastName  = $user_rec->prop("LastName");

    my $dot_pseudonym = lc("$firstName $lastName");

    $dot_pseudonym =~ s/^\s+//;         # Strip leading whitespace
    $dot_pseudonym =~ s/\s+$//;         # Strip trailing whitespace
    $dot_pseudonym =~ s/\s+/ /g;        # Multiple spaces become single spaces
    $dot_pseudonym =~ s/\s/./g;         # Change all spaces to dots
    return $dot_pseudonym;
}

=head2 $a->underbar_pseudonym($user)

Returns the "underbar"-style pseudonym for a user as a string.  For instance, 
underbar_pseudonym("bart") might return "bart_simpson".

=begin testing

my @users     = $a->users();
my $user      = 'bart';
my $rec       = $a->get($user);
my $firstName = $rec->prop("FirstName");
my $lastName  = $rec->prop("LastName");
my $up        = $a->underbar_pseudonym($user);
is($up, "bart_simpson", "Underbar pseudonym created correctly");
my $dp        = $a->dot_pseudonym($user);
is($dp, "bart.simpson", "Underbar pseudonym created correctly");

=end testing

=cut

sub underbar_pseudonym {
    my ($self, $user) = @_;
    my $user_rec = $self->get($user);
    my $firstName = $user_rec->prop("FirstName");
    my $lastName  = $user_rec->prop("LastName");

    my $underbar_pseudonym = lc("$firstName $lastName");

    $underbar_pseudonym =~ s/^\s+//;         # Strip leading whitespace
    $underbar_pseudonym =~ s/\s+$//;         # Strip trailing whitespace
    $underbar_pseudonym =~ s/\s+/ /g;        # Multiple spaces become single spaces
    $underbar_pseudonym =~ s/\s/_/g;         # Change all spaces to underbars
    return $underbar_pseudonym;
}

=head2 $a->activeUsers()

Returns the number of active users, ie, accounts which have passwords set, lock prop is false and
are of type 'user'.

=begin testing

my $numActiveUsers = scalar $a->activeUsers();
like($numActiveUsers, qr/[0-9]+/, "active users returns a number");

=end testing

=cut

sub activeUsers()
{
    my $self = shift;
    my @users = $self->users();
    return unless @users;
    return grep { ($_->prop("__state") || '') eq 'active' } @users;
}

=head2 get_next_uid

Returns the next available UID from /etc/passwd. All UIDs are above the range
reserved for 'system' accounts (currently 5000). 

=for testing
SKIP: {
    skip "Must be root to run get_next_uid" if $<;
    my $u = $a->get_next_uid();
    ok($u > 5000, "UID should be greater than 5000");
    ok(! getpwuid($u), "UID should not yet exist");
}

=cut

sub get_next_uid {
    use esmith::ConfigDB;

    my $id;
    my $db = esmith::ConfigDB->open || die "Couldn't open config db";

    if ($id = $db->get('MinUid'))
    {
        $id = $id->value();
    }
    else
    {
        $db->new_record('MinUid');
        $id = 5000;
    }

    my $maxid = 1 << 31;
    setpwent();
    setgrent();
    while (getpwuid $id || getgrgid $id)
    {
        die "All userids in use" if ($id == $maxid);
        $id++;
    }
    endpwent();
    endgrent();

    $db->set_value('MinUid', $id + 1);

    return $id;
}

=pod

=head2 new_record ($key, \%props)

This method is overridden from esmith::DB::db. We do an additional check
for implicit accounts here - accounts that exist in /etc/passwd but not
in the db. Otherwise it behaves just like the superclass method.

=begin testing

isnt($a->new_record("root", {type=>'system'}), "OK", 
    "can't create existing account");
is($a->get("nobody"), undef, "nobody doesn't exist in db");
isnt($a->new_record("nobody", {type=>'system'}), "OK",
    "can't create account in /etc/passwd");
isnt($a->new_record("screwy", {type=>'user'}), undef, 
    "created a regular user");

=end testing

=cut

sub new_record
{
    my ($self, $key, $props) = @_;

    if(getpwnam($key) || getgrnam($key))
    {
        warn "Attempt to create account '$key' which already exists ",
            "in passwd";
        return undef;
    }
    return $self->SUPER::new_record($key, $props);
}

=pod

=head2 validate_account_name ($name)

Check $name to see if it is a valid account name. Valid account names
start with a letter or number and contain only letters, numbers, 
underscores, dots and dashes.

=begin testing

is($a->validate_account_name("root"), "OK", "root is a valid name");
is($a->validate_account_name("fred.frog"), "OK", "fred.frog is a valid name");
is($a->validate_account_name("jane_doe"), "OK", "jane_doe is a valid name");
isnt($a->validate_account_name("^root"), "OK", "^root is not a valid name");
is(esmith::AccountsDB::validate_account_name("root"), "OK", "called as a function");

=end testing

=cut

sub validate_account_name
{
    my $self;
    # Were we called as a method or a function?
    if($#_ > 0)
    {    
        $self = shift;
    }
    my $name = shift;
    return ($name =~ /[^0-9a-z\-_\.]/ or $name !~ /^[a-z]/) ? undef : 'OK';
}

=head1 AUTHOR

SME Server Developers <bugs@e-smith.com>

See http://www.e-smith.org/ for more information



