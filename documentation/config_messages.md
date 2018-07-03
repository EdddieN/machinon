# Machinon Configuration Messages
Machinon has a second serial port to the host processor board for configuration comms. This keeps the configuration comms separate to the main data comms and allows Domoticz (or similar) to keep a dedicated serial port open for data comms.

The config serial port is connected to the host Raspberry Pi via the SC16IC752 SPI-UART bridge chip. This requires  software support/configuration on the Pi - see Raspberry Pi software setup page.

Configuration commands use the MySensors message format, but with custom message content. Parameter values are generally decimal integer or floating point such as `1023` or `1.004`. Invalid parameters/values will be ignored. Configuration commands should only be sent on the config port.

The Machinon board will respond to requests for existing configuration values. Send a MySensors "req" message with the `<node_id>`, `<child_id>` and `<type>` of the desired parameter. Machinon responds with the corresponding "set" message and the existing value.

## Overall Configuration/Control

### Set Reporting Interval
Send a "set V_VAR1" message with node_id=`0` and child_id=`1`, and value=`<interval>` where `<interval>` is the desired reporting interval in seconds, in the range `10`...`86400`. The default is 60 seconds.

Message format:  
`0;1;1;0;24;<interval>\n`

where `<interval>` is the desired reporting interval in seconds (`10`...`86400`).

Examples:  
`0;1;1;0;24;60` to set reporting interval to 1 minute.  
`0;1;2;0;24;0` to request the existing interval (Machinon ignores the payload parameter and responds with a set V_VAR1 message).

### Force a reboot to trigger bootloader
Send a "set V_VAR2" message with node_id=`0` and child_id=`1`, and value=`<target>` to force a reset and bootload of the main or slave microcontroller.

Message format:  
`0;1;1;0;25;<target>\n`

where `<target>` is:
* `1` to make the main AVR jump to bootloader and wait for an AVR109 protocol bootload (using AVRDude or similar). The bootloader will time out after 5 seconds and run the existing application firmware.  
* `2` to make the main AVR reset the slave (digital inputs) AVR and pass all serial data on the config port through to the slave. The slave bootloader will wait up to 5 seconds for an AVR109 protocol bootload, then time out and run the slave application firmware, which signals to the main AVR to exit the serial passthrough and resume normal operation. The host may also reset the main AVR using GPIO23 to exit the bootload loop.

Examples:  
`0;1;1;0;25;1` to trigger reboot and bootload on main AVR.  
`0;1;1;0;25;2` to trigger reboot and bootload on slave AVR.

### Request Presentation
Send a "internal I_PRESENTATION" message with node_id=`0` and child_id=`1` to make Machinon re-send all MySensors presentation messages on the data port.

Example:  
`1;1;3;0;19;1` to request re-send of all presentation messages.

### Request Firmware Versions
To request main and slave AVR firmware version strings, send a "req V_VAR3" message:

Message format:  
`0;1;2;0;26;1\n`

The Machinon responds with a "set V_VAR3" message in the form:  
`0;1;1;0;24;<main_fw>,<slave_fw>\n`

where:
* `<main_fw>` is the version of the main microcontroller firmware, e.g. `1.10.2`  
* `<slave_fw>` is similar but for the slave (digital inputs) AVR

## Digital Status Inputs Configuration

### Enable/Disable Status Reporting
Send a "set V_VAR1" message with node_id=`1` to enable or disable "report-on-change" for a specified digital input channel. For an enabled channel, Machinon sends a report message immediately when the input changes state.

Message format:  
`1;<channel>;1;0;24;<state>\n`

where:
* `<channel>` = `1`...`16` to specify DIN01...DIN16  
* `<state>` is:
  * `1` to enable report-on-change (default), or  
  * `0` to disable

Examples:  
`1;16;1;0;24;1\n` to enable status reporting for DIN16  
`1;1;2;0;24;0\n` to request the existing setting for DIN01 (Machinon ignores the payload parameter and responds with a set V_VAR1 message).

## Digital Counter Inputs Configuration

### Enable/Disable Counter Reporting
Send a "set V_VAR1" message with node_id=`2` to enable or disable reporting the counter value for a specified input channel.

Message format:  
`2;<channel>;1;0;24;<state>\n`

where:
* `<channel>` = `1`...`16` to specify DIN01...DIN16  
* `<state>` is:
  * `1` to enable the counter for that channel, or  
  * `0` to disable (default)

Examples:  
`2;10;1;0;24;1` to enable counter reporting for DIN10  
`2;16;2;0;24;0` to request the existing setting for DIN16 (Machinon ignores the payload parameter and responds with a set V_VAR1 message).

### Set Counter Multiplier/Offset
Send a "set V_VAR2" message with node_id=`2` to set the slope and offset parameters for scaling the specified counter's values.

Message format:  
`2;<channel>;1;0;25;<offset>,<slope>\n`

where:
* `<channel>` is `1`...`16` to specify DIN01...DIN16  
* `<offset>` and `<slope>` are decimal values

The reported value is: `<counter> * <slope> + <offset>`

Default slope is `1` for all channels (no scaling).  
Default offset is `0` for all channels (no offset).

Examples:  
`2;6;1;0;25;0,0.1` to set DIN06 counter slope to 0.1 and offset to 0 (reported value is count * 0.1)  
`2;1;1;0;25;100,25` to set DIN01 counter slope to 25 and offset to 100 (reported value is count * 25 + 100)  
`2;16;2;0;25;0` to request the existing setting for DIN16 (Machinon ignores the payload parameter and responds with a set V_VAR2 message).

### Set Counter Value Type
Send a "set V_VAR3" message with node_id=`2` to set the type of value that each counter is reported as, either Power/kWh (for electric meters) or Water/Volume(for gas, water etc meters).

Message Format:  
`2;<channel>;1;0;26;<sensor_type>,<value_type>\n`

where:
* `<channel>` = `1`...`16` (to select DIN01...DIN16)  
* `<sensor_type>,<value_type>` is one of the supported MySensors sensor/value type code pairs:
  * `21,35` = Volume as S_WATER / V_VOLUME
  * `13,18` = Energy as S_POWER / V_KWH

NB the `<sensor_type>,<value_type>` pair must be a supported MySensors combination, eg `13,35` is not a standard sensor/value combination.

Examples:  
`2;1;1;0;26;21,35\n` = Report DIN01 Count value as Volume (litres)  
`2;6;1;0;26;13,18\n` = Report DIN06 Count value as Power/Energy in kWh

### Set Counter Raw Value
Send a "set V_VAR4" message with node_id=`2` to set the raw counter value for a specified input.

Message format:  
`2;<channel>;1;0;27;<value>\n`

where:
* `<channel>` is `1`...`16` to specify DIN1...DIN16  
* `<value>` is the (raw) integer value to set the counter to

Examples:  
`2;4;1;0;27;1120` to set DIN04 counter value to 1120 (as if that many pulses had been counted).  
`2;1;1;0;27;0` to reset DIN01 counter value to 0.  
`2;16;2;0;27;0` to request the raw count for DIN16 (Machinon ignores the payload parameter and responds with a set V_VAR4 message).

## Current Transformer Inputs Configuration

### Set CT Scaling Factor
Send a "set V_VAR2" message with node_id=`3` to set the slope (scaling factor) for the specified channel. There is no offset.

The slope parameter scales the raw value (0 at zero input, 1 at full-scale input) to meaningful values. This parameter's value is the CT ratio (when reporting as current), or the product of CT ratio, nominal voltage and nominal power factor (when reporting as power).

Message format:  
`3;<channel>;1;0;25;<slope>\n`

where:
* `<channel>` = `1`...`6` to specify input CT1...CT6  
* `<slope>` is a decimal scaling value. Default is `1` (no scaling).

Examples:  
`3;1;1;0;25;23000` to set CT1 scaling to 23000 (scale 0-1 raw value to watts assuming 230V supply and power factor of 1)  
`3;1;2;0;25;0` to request the existing setting for CT1 (Machinon ignores the payload parameter and responds with a set V_VAR2 message).

### Set CT Value Type
Sets the type of the reported values to either Power/Watts or Current/Amps. The slope (scaling factor) must separately be set to match this.

Message Format:  
`3;<channel>;1;0;26;<sensor_type>,<value_type>\n`

where:
* `<channel>` = `1`...`6` to specify input CT1...CT6  
* `<sensor_type>,<value_type>` is one of the supported MySensors sensor/value type code pairs:
  * `30,39` = Current as S_MULTIMETER / V_CURRENT
  * `13,17` = Power as S_POWER / V_WATT

NB the `<sensor_type>,<value_type>` pair must be a supported MySensors combination, eg `30,17` is not a standard sensor/value combination.

Examples:  
`3;1;1;0;26;30,39\n` = Report CT1 value as Current in Amps  
`3;6;1;0;26;13,17\n` = Report CT6 value as Power in Watts

### Set CT Calibration Factor
The CT measurement circuit may have a small error (common to all inputs) that can be calibrated out if desired. Send a "set V_VAR4" message with node_id=`3` to set the calibration factor.

Message format:  
`3;1;1;0;27;<cal>\n`

where `<cal>` is a decimal calibration value, eg `1.02` or `0.98` that adjusts the measured current on all channels to give correct 0...1 output. Default is `1` (no calibration).

Examples:  
`3;1;1;0;27;1.015` to apply a +1.5% correction if the device reads 1.5% low.
`3;1;2;0;27;0` to request the existing calibration factor (Machinon ignores the payload parameter and responds with a set V_VAR4 message).

## Analogue Inputs Configuration

### Set Analogue Input Mode
Send a "set V_VAR1" message with node_id=`4` to set the specified analogue input channel to one of the supported voltage/current modes: 0-10V, 4-20mA or 0-20mA. Default mode value is `0` for all channels (0-10V mode).

Message format:  
`4;<channel>;1;0;24;<mode>\n`

where:  
* `<channel>` = `1`...`8` to specify AIN1...AIN8
* `<mode>` selects the input mode/range:
  * `0` = 0-10 V range (default)
  * `1` = 4-20 mA range
  * `2` = 0-20 mA range

Examples:  
`4;1;1;0;24;1\n` = set AIN1 to 4-20 mA mode.  
`4;2;1;0;24;0\n` = set AIN2 to 0-10 V mode.  
`4;1;2;0;24;0\n` = request the existing setting for AIN1 (Machinon ignores the payload parameter and responds with a set V_VAR1 message).

### Set Analogue Input Multiplier
Send a "set V_VAR2" message with node_id=`4` to set the slope and offset parameters which scale the raw analogue value to a meaningful range. Machinon multiplies the raw value 0...1 by the slope, then adds the offset.

Default values are `<slope>`=`1` and `<offset>`=`0` which give as output the raw analogue value 0...1

Message format:  
`4;<channel>;1;0;25;<offset>,<slope>\n`

where:
* `<channel>` = `1`...`8` to specify AIN1...AIN8  
* `<slope>` = the scaling factor that converts raw range 0...1 to the desired output range.  
* `<offset>` is the offset to add to the scaled value to shift the output range up or down.

Raw value `0` represents minimum nominal input (eg 0V or 4mA) and raw value `1` represents maximum nominal input (10V or 20mA). Over/underrange readings may result in raw values outside this range (eg `-0.25` raw for 0mA when mode is 4-20mA, or `1.2` raw with 12V input when mode is 0-10V).

Examples:  
`4;3;1;0;25;0,30\n` = set AIN3 to report as 0-30V when configured for 0-10V input mode.  
`4;1;1;0;25;0,-10,120\n` = set AIN1 to report as `-10`...`+110` when configured for 4-20mA input mode, for a 4-20mA temperature transmitter/sensor with range -10 to +110C (Span = -10 + 110 = 120 so use this as multiplier. Raw value for 4mA is 0, so just use -10 as offset)  
`4;1;2;0;25;0\n` = request the existing offset/multiplier for AIN1. Machinon ignores the payload parameter and responds with a set V_VAR2 message such as: `4;1;1;0;25;-10,120\n`

### Set Analogue Value Type
Send a "set V_VAR3" message with node_id=`4` to set the type of MySensors message that Machinon generates, so that MySensors host will show correct units, sensor type etc (eg voltage, current, temperature, pressure, humidity, power…).

Message format:  
`4;<channel>;1;0;26;<sensor_type>,<value_type>\n`

where:
* `<channel>` = `1`...`8` to specify AIN1...AIN8  
* `<sensor_type>,<value_type>` is one of the supported MySensors sensor/value type code pairs:
  * `6,0` = Temperature as S_TEMP / V_TEMP
  * `7,1` = Humidity as S_HUM / V_HUM
  * `30,38` = Voltage as S_MULTIMETER / V_VOLTAGE
  * `30,39` = Current as S_MULTIMETER / V_CURRENT
  * `13,17` = Power as S_POWER / V_WATT (for current transducer etc)
  * `16,37` = Level (%) as S_LIGHT_LEVEL / V_LEVEL
  * `23,48` = Other/Custom as S_CUSTOM / V_CUSTOM

NB the `<sensor_type>,<value_type>` pair must be a supported MySensors combination, eg `6,1` is not a standard sensor/value combination.

Example:  
`4;1;1;0;26;6,0\n` = Set AIN1 to report as a temperature sensor.

## Digital Outputs Configuration

### Set Default Powerup State
The Digital Outputs can be set to start in the on or off state when MiniBMS powers up, before any "set V_STATUS" commands are received. This can be used for outputs/actuators that are normally on, to avoid any switch-on delay as the system boots up.

Send a "set V_VAR1" message with node_id=`5` to configure the specified channel.

Message format:  
`5;<channel>;1;0;24;<state>\n`

where:
* `<channel>` is `1`...`16` to specify DOUT01...DOUT16  
* `<state>` is:
  * `1` to set the powerup state to on/active for that channel, or  
  * `0` to set the powerup state to off/inactive for that channel (default)

Examples:  
`5;16;1;0;24;1\n` to make DOUT16 on/active at startup.  
`5;16;2;0;24;0\n` to request the existing setting for DOUT16 (Machinon ignores the payload parameter and responds with a set V_VAR1 message).

## Front Panel LCD, LEDs, and Keypad Configuration

### Write Text to LCD Line
Same as for host data comms (see host data comms section)

### Keypad Press/Release
Same as for host data comms (see host data comms section)

### Keypad Event Configuration
The keypad event messages can be sent to either the host data port, or the host config port.

Send a "set V_VAR1" message with node_id=`6` to configure keypress events. The configuration applies to all keys.

Message format:  
`6;11;1;0;24;<port>\n`

where `<port>` is:
* `0` to disable keypad events
* `1` to send event messages to host config port only (default)
* `2` to send event messages to host data port only
* `3` to send event messages to both ports

Examples:  
`6;11;1;0;24;2` to send keypad messages to data port.  
`6;11;2;0;24;0` to request the existing setting (Machinon ignores the payload parameter and responds with a set V_VAR1 message).

### Status LED 1 Colour
The main status LED can be set to flash GREEN (default, normal operation) or RED (error condition). Send a "set V_STATUS" message with node_id=`6` and child_id=`16` to set the LED colour.

Message format:  
`6;16;1;0;2;<colour>\n`

where `<colour>` is:
* `0` for green (default), or
* `1` for red

Examples:  
`6;16;1;0;2;1\n` to set the status LED colour to red  
`6;16;2;0;2;0\n` to request the existing setting (Machinon ignores the payload parameter and responds with a set V_STATUS message).

### Status LED 2 On/Off
The secondary status LED can be set ON or OFF (default). Send a "set V_STATUS" message with node_id=`6` and child_id=`17`.

Message format:  
`6;17;1;0;2;<state>\n`

where <state> is:
* `0` = OFF (default), or
* `1` = ON

Examples:  
`6;17;1;0;2;1` to set status 2 LED on.  
`6;17;1;1;2;0` to set status 2 LED off and request acknowledgement.
