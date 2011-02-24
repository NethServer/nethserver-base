#!/usr/bin/perl -w

use strict;
use Test::More 'no_plan';
use esmith::FormMagick::Tester;

my $ua = new esmith::FormMagick::Tester;

ok($ua->get("http://localhost/server-manager"), "Get server manager");
is($ua->{status}, 200, "200 OK");
