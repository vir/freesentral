#! /bin/sh

export IFS='	'
while read name text; do
    f="$name.au"
    if [ -f "$f" ]; then
	echo "$f already exists, skipping..."
    else
	echo "$f: $text"
	echo "$text" | text2wave -scale 4 -F 8000 -otype ulaw | sox -t raw -r 8000 -c 1 -U - "$f"
    fi
done << EOF
deleted	This message was deleted
greeting	Voicemail
menu	To listen to the first message press 0, to jump to the previous message press 7, to replay the current message press 8, to jump to the next message press 9, to record your greeting press 1, to listen to your greeting press 2, to exit press 3, to listen to this menu again press *
nogreeting	Voicemail	
novmail	This number is not recognized in the system
password	Password
usernumber	Usernumber
EOF