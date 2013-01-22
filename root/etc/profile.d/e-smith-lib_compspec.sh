_esmith_signal-event ()
{
    if [ ! $(which $1 2>/dev/null) ]; then return 0; fi 
    local cur; cur=${COMP_WORDS[$COMP_CWORD]}

    case $COMP_CWORD in
    1)
    	COMPREPLY=( $(find /etc/e-smith/events/ -maxdepth 1 -type d \
		      \( -name 'actions' -prune -o \
		         -name "$cur*" -printf "%f\n" \)) )
	;;
    *) 	;;
    esac

    return 0
}
complete -F _esmith_signal-event signal-event


_esmith_expand-template ()
{
    if [ ! $(which $1 2>/dev/null) ]; then return 0; fi 
    local cur; cur=${COMP_WORDS[$COMP_CWORD]}
    case $COMP_CWORD in 
    1) # need to distinguish between templates and fragments
    	COMPREPLY=( $(find /etc/e-smith/templates \
	                       /etc/e-smith/templates-custom \
	                  -regex "/etc/e-smith/templates\(-custom\)?$cur.*" \
	                  -printf "[ -f /%P ] && echo /%P\n" \
	                  | sh | uniq) )
	;;
    *) 	;;
    esac

    return 0
}
complete -F _esmith_expand-template expand-template


_esmith_db ()
{
    if [ ! $(which $1 2>/dev/null) ]; then return 0; fi 
    local cur; cur=${COMP_WORDS[$COMP_CWORD]}

    case $COMP_CWORD in
    1) # config file
	COMPREPLY=( $(find /var/lib/nethserver/db -maxdepth 1 -type f \
			\( -name '.*' -prune -o \
			   -name "$cur*" -printf "%f\n" \)) )
	;;
    2) # subcommand 
	COMPREPLY=( $(/sbin/e-smith/db 2>&1 |awk '{print $3}' \
			|grep "^$cur" ) )
	;;
    3) 	# key 
	local file; file=${COMP_WORDS[1]}
	local cmd; cmd=${COMP_WORDS[2]}
	local haskey
	haskey=$(/sbin/e-smith/db 2>&1 | grep "dbfile $cmd" | awk '{print $4}')
	if [ -n "$haskey" ]; then
	    COMPREPLY=( $(/sbin/e-smith/db $file keys |grep "^$cur") )	
	fi
	;;
    *) # type/prop/val
	local file; file=${COMP_WORDS[1]}
	local cmd; cmd=${COMP_WORDS[2]}
	local key; key=${COMP_WORDS[3]}
	local i; i=$COMP_CWORD
	local prev
	local valtype
	while [ "$valtype" == "..." ] || [ "$valtype" == "" ]; do
	    prev=${COMP_WORDS[$[i-1]]}
	    PAT='$3'
	    for j in $(seq 4 $[i+1]); do PAT="$PAT,\$$j"; done
	    valtype=$(/sbin/e-smith/db 2>&1 | awk "{print $PAT}" \
		 | grep "^$cmd" | awk "{print \$$[i-1]}")
	    i=$[i-2]
	done
	case $(echo "$valtype" |sed -e 's/[][0-9]//g') in
        "type")  COMPREPLY=( $(/sbin/e-smith/db $file gettype $key \
				| grep "^$cur") )	
	    ;;
        "prop")  
		 COMPREPLY=( $(/sbin/e-smith/db $file printprop $key \
				| sed -e 's/=.*//' | grep "^$cur") )	
	    ;;
	"val")   COMPREPLY=( $(/sbin/e-smith/db $file getprop $key $prev \
				| grep "^$cur"))
	    ;;
 	*)  ;;
	esac
	;;
    esac

    return 0
}
complete -F _esmith_db db


_esmith_config ()
{
    cmd=$(echo $1 | sed -e 's/config$/db/')
    COMP_WORDS=($cmd ${COMP_WORDS[*]})
    COMP_WORDS[1]=configuration
    COMP_CWORD=$[ $COMP_CWORD + 1 ]
    _esmith_db $*
    return $?
}
complete -F _esmith_config config
