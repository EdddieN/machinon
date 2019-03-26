--[[
Domoticz dzVents Lua script for Machinon2
MGC 2019-01-08

Displays the device's Ethernet IP (either DHCP or static) on Machinon2 LCD using a shell script to get the local IP (or "unknown" if disconnected)

This LUA script expects to find the Machinon scripts folder at /opt/machinon.

You need to add the "LCD Line 3" device to the Domoticz system before running this script.
Go to Settings > Devices. Find the "LCD Line 3" device and click on the green arrow button (Add Device).

Change the "LCD Line 3" device name and the path to the script to suit your Machinon setup.

]]

return {
	-- trigger
	-- can be a combination:
	on = {

		-- timer riggers
		timer = {
			-- timer triggers.. if one matches with the current time then the script is executed
			-- 1 minute is shortest interval and also the timer granularity???
			'every 1 minutes'
		}
	},

	-- custom logging level for this script
	logging = {
        level = domoticz.LOG_DEBUG,
        marker = "show-ip script"
    },

	-- actual event code
	-- the second parameter is depending on the trigger
	-- when it is a device change, the second parameter is the device object
	-- similar for variables, scenes and groups and httpResponses
	-- inspect the type like: triggeredItem.isDevice
	execute = function(domoticz)
    	domoticz.log('Running show-ip script...', domoticz.LOG_INFO)

        local cmd = "/opt/machinon/scripts/get-ip.sh"
        local f = assert(io.popen(cmd, 'r'))
        local s = assert(f:read('*a'))
        f:close()
        -- strip out any /r/n etc
        s = string.gsub(s, '^%s+', '')
        s = string.gsub(s, '%s+$', '')
        s = string.gsub(s, '[\n\r]+', '')
    	if s == '' then
    	    s = 'unknown'
        end
    	domoticz.log('Got IP = "' .. s .. '"', domoticz.LOG_INFO)

        domoticz.devices('LCD Line 3').updateText('IP=' .. s)
	end
}
