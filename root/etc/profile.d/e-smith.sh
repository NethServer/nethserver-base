# /etc/profile.d/e-smith.sh - Custom additions for SME servers

if [ "$USER" = "root" ]
then
export PATH=/sbin/e-smith:$PATH
fi
