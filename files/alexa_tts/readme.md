All rights go to Lotzimmer, his script and original log can be found here:

https://blog.loetzimmer.de/2017/10/amazon-alexa-hort-auf-die-shell-echo.html

You will need also JQ https://stedolan.github.io/jq/

# Step 1
Download https://github.com/EdddieN/machinon/blob/master/files/alexa_tts/Alexa_tts.sh to your Raspberry Pi
# Step 2
Edit the username and password for your alexa account
# Step 3
Install https://stedolan.github.io/jq/
# Step 4
Run on CLI `./alexa_tts.sh -a`
This should return after loggin in and creating a cookie via CURL the devices you have under your account
# Step 5
Try on CLI `./alexa.tts.sh -d [your device name, case sensitive] -e speak:'Hello, I am Alexa'`

That is it, you can experiement wiht more commands by typing `./alexa_tts.sh` , if you want more information, best to go to the orignal blog post above
