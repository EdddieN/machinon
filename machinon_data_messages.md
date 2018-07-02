# Machinon Data Messages

The Machinon I/O board sends formatted messages to the processor (host) board serial port at specified interval (for counters and analogue inputs), and on input change (digital status inputs). It also accepts control messages from the host to set digital output states and optionally set other control parameters.

The Machinon uses MySensors serial gateway protocol 2.x to allow easy integration with Domoticz, HomeAssistant, HomeGenie, OpenHAB or similar automation software. See https://www.mysensors.org/download/serial_api_20 for protocol details, and https://www.mysensors.org/about for general information.

The Machinon serial ports operate at 115200 bit/s, 8 data bits, no parity, 1 stop bit. The main data port is connected to pins 8 (TXD to Machinon) and 10 (RXD from Machinon) which correspond to Raspberry Pi TXD and RXD pins. On Raspberry Pi 3, the "miniuart" and main BCM UART must be swapped in order to give reliable serial port operation. See the software setup section for details.

## MySensors Data Messages

MySensors messages are in the form:

```node_id;child_sensor_id;command;ack;type;payload\n```

```node_id``` specifies the group of inputs/outputs on the Machinon board:
1 = Digital status inputs (1 or 0, ON or OFF for each input)
2 = Non-volatile counter inputs
3 = Current Transformer inputs
4 = Analogue Inputs
5 = Digital Outputs
6 = Front panel LCD, buttons and LED indicators

```child_sensor_id``` is the channel number, eg "1" for DIN1

```command``` is the function of the message:
* `set` (1) indicates the Machinon board is sending a data value to the host, OR the host is writing a value to an output or configuration parameter on the Machinon.
* `presentation` (0) messages are sent from the Machinon after startup (or on demand) to present/describe the sensor types/channels to the host.
* `internal` (3) is sent from the Machinon to report internal data such as software version.

```ack``` is normally "0" to indicate a message that does not require acknowledgement. The host may use ack=1 to request acknowledgement of a command (for example when setting a DOUT output), and the Machinon board will respond with the same message to confirm receipt.

```type``` describes the type or meaning of the message data.
* For "set" data reported from digital state inputs:
    * V_STATUS (type 2) for digital status inputs. Value of 0 = off/inactive, 1 = on/active
* For "set" data from digital counters:
    * V_KWH (type 18) when reading as energy
    * V_VOLUME (type 35) when reading as gas/water etc.
* For "set" data from CT inputs:
    * V_CURRENT (type 39) when reading as calculated current (amps)
    * V_WATT (type 17) when reading as estimated power (watts)
    * V_LEVEL (type 37) when sending frequency (Hertz) (from CT
* For "set" data from analogue inputs:
    * V_TEMP (type 0) when reading as temperature
    * V_HUM (type 1) when reading as humidity
    * V_PERCENTAGE (type 3) when reading as unscaled input (0% at 4mA/0V and 100% at 20mA/10V)
    * V_WATT (type 17) when reading as power (watts)
    * V_LEVEL (type 37) when reading as custom value.
    * V_VOLTAGE (type 38) when reading as raw voltage
    * V_CURRENT (type 39) when reading as raw current (mA)
* For "set" data to digital outputs:
    * V_STATUS (type 2). Value 0 = off/inactive, 1 = on/active

Messages are terminated with a line feed character with byte value 0x0A and notated `\n`.

### Digital Status Inputs

Machinon sends a "set V_STATUS" (type=2) message immediately for each input state change, with a minimum period between reports to avoid flooding the host (see specification table). If the input changes rapidly, only the most recent state change will be reported.

Message format is:
`1;<channel>;1;0;2;<status>\n`
where <channel> is 1...16 and <status> is 0 (inactive/off) or 1 (active/on state)

Example:
`1;1;1;0;2;0\n` = channel 1 status has changed to "off"

The status inputs can individually be enabled/disabled. Refer to the Configuration Comms section for details.

### Digital Counter Inputs

All enabled counters are reported at the data interval in a group of up to 16 messages.

Message format:
`1;<channel>;1;0;<type>;<count>\n`
where:
* <channel> is the input number 1...16
* <type> is the configured value type (V_KWH or V_VOLUME)
* <count> is the current counter value, scaled and offset with that channel's slope and offset parameters.

Example:
`2;7;1;0;18;123.4\n` = channel 7 value is 123.4 kWh
`2;10;1;0;35;567.89\n` = channel 10 value is 567.89 litres

The counters can individually be enabled/disabled and configured with slope/offset and value type. Refer to the Configuration Comms section for details.

### Current Transformer Inputs

CT values are reported as V_CURRENT or V_WATT messages at the specified data interval. The value is the mean average over the interval.

Message format:
`3;<channel>;1;0;<type>;<value>\n`
where:
* <channel> is the input number 1...6
* <type> is the value type (39=V_CURRENT or 17=V_WATT)
* <value> is the average value over the interval.

Example:
`3;4;1;0;17;500.3\n` = channel 4 value is 500.3 Watts
`3;6;1;0;39;12.3\n` = channel 6 value is 12.3 Amps

The average frequency of the CT4 input is reported in Hz as V_LEVEL and child_id=7:
`3;7;1;0;37;50.01\n` = 50.01 Hz

The slope/offset and value type can be configured for each input. Refer to the Configuration Comms section for details.

### Analogue Inputs

Analogue values are reported at the specified data interval, with <type> as the selected value type for each channel. The value is the average over the interval, and is scaled/offset using the configured parameters for each input.

Message format:
`4;<channel>;1;0;<type>;<value>\n`
where
* <channel> is the input number 1...8
* <type> is the configured value type (V_VOLTAGE, V_TEMPERATURE etc)
* <value> is the average value over the interval.

Example:
`4;4;1;0;38;2.34\n` = AIN4 value is 2.34 volts
`4;7;1;0;1;45.6\n` = AIN7 value is 45.6 % humidity (eg from a humidity transmitter)
`4;8;1;0;0;25.0\n` = AIN8 value is 25.0 degrees (eg from a temperature transmitter)

Machinon supports several MySensors sensor and value types:
* 0 = S_TEMP / V_TEMP
* 1 = S_HUM / V_HUM
* 38 = S_MULTIMETER / V_VOLTAGE
* 39 = S_MULTIMETER / V_CURRENT
* 17 = S_POWER / V_WATT (for current transducer etc)
* 4 = S_BARO / V_PRESSURE
* 37 = S_LIGHT_LEVEL / V_LEVEL
* 48 = S_CUSTOM / V_CUSTOM

Refer to the Configuration Comms section for how to set the value type and slope/offset.

### Digital Outputs

Machinon accepts a "set V_STATUS" message with node_id=5 to set the digital outputs.

Message format:
`5;<channel>;1;0;2;<status>\n`
where
* <channel> is output number 1...16
* <status>=0 for off, or 1 for on.

Example:
`5;1;1;0;2;0\n` = set output DOUT1 to OFF
`5;2;1;0;2;1\n` = set output DOUT2 to ON

### Front Panel LCD, Keypad and Supply

#### LCD Text

Machinon accepts "set V_TEXT" messages with node_id=6 to write text on a specified line on the LCD. It is not possible to read back the existing text from the display.

Message format:
`6;<line>;1;0;47;<text>\n`
where
* <line> is LCD line 1...7 (top to bottom)
* <text> is the message to display, up to 21 characters. Longer messages will be truncated. Shorter messages will be padded to blank the rest of the line. Most standard ASCII characters 0x20...0x7F can be displayed. Ensure that the text does not contain any control characters (linefeed \n is used as the message terminator).

Example:
`6;1;1;0;47;Hello World!\n` = display "Hello World!" message on top line.

#### Keypad

Machinon sends "set V_STATUS" messages with node_id=6 to indicate keypress/release events. A value of 1 indicates keypress, and 0 indicates release. The keys are debounced and each keypress has a duration of at least 100 ms. There is no auto-repeat.

Message format:
`6;<button>;1;0;2;<state>\n`
where:
* <button> = 11 for top button … 14 for bottom button
* <state> = 1 for button pressed, or
* <state> = 0 for button released

Example:
`6;12;1;0;2;1\n` = key number 12 (2nd from top) is now pressed.
`6;14;1;0;2;0\n` = key number 14 (bottom) is now released.

#### Status LED 1 Colour
To set the status/heartbeat LED colour (green or red), send a "set V_STATUS" message with node_id=6 and child_id=16. Value=0 for green (default), or 1 for red (to indicate error etc).

Message format:
`6;16;1;0;2;<colour>\n`
where:
* <colour> = 0 for green (default, indicates no errors), or
* <colour> = 1 for red (to indicate error condition etc)

Example::
`6;16;1;0;2;1\n` = set the LED1 colour to red

#### Status LED 2 On/Off
To set the "Status 2" green LED on/off, send a "set V_STATUS" message with node_id=6 and child_id=17. Value=0 for off (default), or 1 for on (to indicate error etc).

Message format:
`6;17;1;0;2;<state>\n`
where:
* <state> = 0 for OFF (default), or
* <state> = 1 for ON (to indicate error condition etc)

Example:
`6;17;1;0;2;1\n` = turn status LED 2 on

#### Supply Voltage
Machinon sends "set V_VOLTAGE" messages with node_id=6 and child_id=15 to report the supply voltage in the range 10...28 Volts. The value is the average over the reporting interval.

Message format:
`6;15;1;0;38;<voltage>\n`
where <voltage> is the supply voltage in volts.

Example:
`6;15;1;0;38;23.9\n` = supply voltage is 23.9 volts

### Presentation of Sensor Information

Machinon presents all of its I/O channels as "sensors" to the host software by sending a set of MySensors "presentation" (type=0) messages after startup or when an I_PRESENTATION request message is received (typically after the host automation application has started).

The presentation messages describe the type of sensor/data and the name of each channel so that the host software can correctly handle the data. Refer to MySensors documentation for details. For inputs that are configurable, the presentation messages report the configured "mode" or data type (eg analogue inputs are presented as temperature or voltage or current etc).

To request presentation of sensor information, send an I_PRESENTATION internal message to node 0 and child 1 in the form:
`0;1;3;0;19;1\n`

Machinon also sends a presentation message after the data type of a channel is changed, eg when changing an analogue input from "voltage" to "temperature", or a digital counter from "energy" to "volume".

Example presentation messages from the Machinon board:  
`1;1;3;0;11;DI Status` = internal msg, node 1, child 1, I_SKETCH_NAME  
`1;1;3;0;12;0.1.`2 = internal msg, node 1, child 1, I_SKETCH_VERSION  
`1;1;0;0;3;DI1 Statu`s = present node 1, child 1 as S_BINARY with given name.  

`2;1;0;0;13;DI1 Count` = present node 2, child 1 as S_POWER with name.  
`2;1;3;0;11;Digital Counters` = internal msg, node 2, child 1, I_SKETCH_NAME  
`2;1;3;0;12;0.1.2` = internal msg, node 2, child 1, I_SKETCH_VERSION  

`4;1;0;0;30;AN1` = present node 4, child 1 as S_MULTIMETER with name.  
`4;1;3;0;11;Analogue Inputs` = internal msg, node 4, child 1, I_SKETCH_NAME  
`4;1;3;0;12;0.1.2` = internal msg, node 4, child 1, I_SKETCH_VERSION  
