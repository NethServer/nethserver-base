#!/usr/bin/perl -w

# Override set_value, delete, set_prop and delete_prop functions in
# esmith::ConfigDB to provide UnsavedChanges automatically
package esmith::ConfigDB::unsaved;
use strict;
use warnings;
require esmith::ConfigDB;
@esmith::ConfigDB::unsaved::ISA = qw(esmith::ConfigDB);

sub set_value {
    my ($self, $key, $value) = @_;

    # The 'UnsavedChanges' entry is automatically set to 'yes'
    # when a system parameter is changed. This means that there
    # are changes to the main e-smith configuration file which
    # need to be 'saved' (i.e.  all of the e-smith config files
    # must be updated). However, don't do anything automatic if
    # the caller is deliberately trying to set the UnsavedChanges
    # flag. (That's how they can reset it.)

    my $current_value = $self->SUPER::get_value($key);
    return $current_value if (defined $current_value and $current_value eq $value);

    if ($key ne 'UnsavedChanges') {
	$self->SUPER::set_value('UnsavedChanges', 'yes');
    }

    return $self->SUPER::set_value($key, $value);
}
sub set_prop {
    my ($self, $key, $prop, $value) = @_;

    my $rec = $self->get($key);
    return unless ($rec);
    my $current_value = $rec->prop($prop);
    return $current_value if (defined $current_value and $current_value eq $value);

    $self->SUPER::set_value('UnsavedChanges', 'yes');
    return $rec->set_prop($prop, $value); 
}
sub delete_prop {
    my ($self, $key, $prop) = @_;
    my $rec = $self->get($key);
    return unless (defined $rec); # do nothing if the key doesn't exist
    my $current_value = $rec->prop($prop);
    return unless (defined $current_value);

    $self->SUPER::set_value('UnsavedChanges', 'yes');
    return $rec->delete_prop($prop);
}
# Deleting a record is the same as changing one
sub delete {
    my ($self, $key) = @_;
    my $current = $self->get($key);
    return unless (defined $current);
    $self->SUPER::set_value('UnsavedChanges', 'yes');
    return $current->delete;
}
1;
