#!/usr/bin/perl -w
# vim: se ft=perl:

use strict;

use Test::More 'no_plan';
use User::pwent;
use User::grent;
use File::stat;
use esmith::AccountsDB;
my $adb = esmith::AccountsDB->open;


### Check the admin account is in order.
my $admin = getpwnam('admin');
ok( $admin,             'admin user exists' );
is( $admin->shell, '/sbin/e-smith/console', 'shell' );

# Check for the existence of these groups.
my @groups = qw(shared www slocate ntp);
foreach my $group_name (@groups)
{
    ok( getgrnam($group_name), "$group_name group exists" );
}

#  Check the groups that the admin user should be a member of.
foreach my $group_name (qw(root shared www)) {
    my $group = getgrnam($group_name);
    ok( grep($_ eq 'admin', @{ $group->members }), 
                        "admin is in group $group_name" );
}

# Check that all users in the AccountsDB are in the passwd file.
foreach my $user ($adb->users)
{
    my $name = $user->{key};
    ok( getpwnam($name), "$name from accounts db exists in passwd file" );
}

# Check that all groups in the AccountsDB are in the group file.
foreach my $group ($adb->groups)
{
    my $name = $group->{key};
    ok( getgrnam($name), "$name from accounts db exists in group file" );
}

# Check for the existence of these users. 
my @users = qw(public www root admin public);
foreach my $user_name (@users)
{
    ok( getpwnam($user_name), "$user_name user exists" );
}

# Make sure that user www belongs to admin and shared groups.
foreach my $group_name (qw(admin shared))
{
    my $group = getgrnam($group_name);
    ok( grep($_ eq 'www', @{ $group->members }),
    	"www is in group $group_name" );
}

# Check that unwanted accounts don't exist.
foreach my $user (qw(halt shutdown sync)) {
    ok( !getpwnam($user), "unwanted $user account" );
}

# Check the shells of the root and admin users.
ok( (getpwnam('admin')->shell eq '/sbin/e-smith/console'), 'admin shell is /sbin/e-smith/console' );
ok( (getpwnam('root')->shell eq '/bin/bash'), 'root shell is /bin/bash' );

# Check ownership and permissions of important files.
# These files may not exist, thanks to the breakup of the base. Make the tests
# conditional on their existence.
my %dirs = (
            '/home/e-smith' => { user   => 'admin',
                                 group  => 'admin',
                                 mode   => 040755
                               },
            '/home/e-smith/files' => {
                                      user   => 'root',
                                      group  => 'root',
                                      mode   => 040755,
                                     },
            '/home/e-smith/files/users/admin' => {
                                                  user   => 'admin',
                                                  group  => 'admin',
                                                  mode   => 040500,
                                                 },
            '/home/e-smith/Maildir' => {
                                        user   => 'admin',
                                        group  => 'admin',
                                        mode   => 040700,
                                       },
             '/etc/e-smith/web' => {
                                    user    => 'root',
                                    group   => 'root',
                                    mode    => 0755,
                                   },
            '/etc/e-smith/web/functions' => {
                                             user   => 'root',
                                             group  => 'admin',
                                             mode   => 0550,
                                            },
            '/etc/e-smith/web/panels'    => {
                                             user   => 'root',
                                             group  => 'admin',
                                             mode   => 0550,
                                            },
            '/etc/e-smith/web/common'    => {
                                             user   => 'www',
                                             group  => 'admin',
                                             mode   => 0550,
                                            },
            '/etc/e-smith/web/panels/password/cgi-bin/userpassword' => 
            {
             user   => 'root',
             group  => 'admin',
             mode   => 06550,
            },
            '/usr/lib/apache/pwauth'    => {
                                            user    => 'root',
                                            group   => 'www',
                                            mode    => 04550,
                                           },
            '/usr/lib64/apache/pwauth'    => {
                                            user    => 'root',
                                            group   => 'www',
                                            mode    => 04550,
                                           },
           );

while(my($dir, $setup) = each %dirs) {
    my $stat = stat($dir);
    SKIP: {
	skip "$dir does not exist", 3 unless defined $stat;
	is( $stat->uid,  getpwnam($setup->{user})->uid,  "owner of $dir" );
	is( $stat->gid,  getgrnam($setup->{group})->gid, "group of $dir" );
	SKIP: {
	    skip "No mode expectations for $dir", 1 unless $setup->{mode};
	    cmp_ok( $stat->mode & $setup->{mode}, '==', $setup->{mode}, 
						    "perms for $dir" );
	}
    }
}

my %files = (
             '/home/e-smith/files/' => {
                                        user    => 'root',
                                        group   => 'root',
                                        mode    => 0755
                                       },
             '/home/e-smith/files/ibays/Primary' => {
                                        user    => 'admin',
                                        group   => 'shared',
                                        mode    => 02750,
                                       },
             '/etc/e-smith/web/functions'   => {
                                        user    => 'root',
                                        group   => 'admin',
                                        mode    => 04750,
                                       },
             '/etc/e-smith/web/panels'      => {
                                        user    => 'root',
                                        group   => 'root',
                                        mode    => 0755,
                                       },
            );

while( my($dir, $setup) = each %files ) {
    opendir DIR, $dir || die $!;
    foreach my $file (readdir DIR) {
        next if $file =~ /^\.{1,2}$/;
        $file = "$dir/$file";
	next if -l $file;
        my $stat = stat($file);
        is( $stat->uid,  getpwnam($setup->{user})->uid,  "owner of $file" );
        is( $stat->gid,  getgrnam($setup->{group})->gid, "group of $file" );
        cmp_ok( $stat->mode & $setup->{mode}, '==', $setup->{mode}, 
                                                        "perms for $file" );
    }
    close DIR;
}

my %name2type =
    (
	admin           => 'system',
	mysql           => 'system',
	shared          => 'system',
        everyone        => 'pseudonym',
        'mailer-daemon' => 'pseudonym',
        postmaster      => 'pseudonym',

        'cgi-bin'           => 'url',
        'e-smith-manager'   => 'url',
        'e-smith-password'  => 'url',
        'server-manager'    => 'url',
        'server-manual'     => 'url',
        'user-password'     => 'url',
        'common'            => 'url',
        'files'             => 'url',
        'icons'             => 'url',
        webmail             => 'url',
	'Primary'           => 'ibay',
     );

my $account;
while( my($name, $type) = each %name2type ) {
    SKIP: {
	skip "$name is not defined", 2 unless $adb->get($name);
	isa_ok( $account = $adb->get($name),  'esmith::DB::Record', "$name" );
	is( $account->prop('type'), $type,       '  type' );
    }
}

my %Expected_Props = 
  (
   shared       => { Visible => 'internal' },
   everyone     => { Account    => 'shared',
                     Visible    => 'internal'
                   },
   'mailer-daemon' => { Account => 'admin' },
   postmaster      => { Account => 'admin' }
  );

while( my($name, $exp_props) = each %Expected_Props ) {
    my $account = $adb->get($name);
    my %props = $account->props;
    is_deeply( [@props{keys %$exp_props}], [@{$exp_props}{keys %$exp_props}],
                                                "$name props");
}
