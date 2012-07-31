#
# /etc/profile.d/nethserver.sh -- Custom additions for NethServer environment
#

if [ "$USER" = "root" ]; then
  export PATH=/sbin/e-smith:$PATH
fi

# Feature #1224 -- Date and time in Bash history
export HISTTIMEFORMAT="%Y-%m-%d %T " 

