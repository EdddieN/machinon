# machinon Hardware Specification
## Mechanical
* Enclosure: Standard top-hat DIN-rail mount, 12-module wide (W 213 mm), also wall mountable via screw slots/tabs.
* I/O on standard 10-way and 12-way 5mm pitch plug-in terminal blocks
* Status indicator LEDs and LCD on front panel.
* https://github.com/EdddieN/machinon/blob/master/documentation/machinon_dimmensions.pdf
## Power
* Supply: 10-28 V DC input (typ. 12 V or 24 V)
  * Typical consumption 200-500 mA at 12 V with Raspberry Pi 3B
* 2 Aux 24 V outputs (max 1 A total) for powering digital inputs, relays etc.
* 1 Aux 5 V output (max 100 mA) for small analogue sensors
## Digital Inputs
* 16 digital inputs, reported as either:
  * Logical state ON/OFF
    * Current state reported at each transition
    * Minimum 100 ms between transitions
  * Cumulative pulse count
    * 32 bit counters
    * Minimum 50 ms pulse width (max 10 Hz input frequency)
    * Optional scale and offset parameters allow output values to be presented as meter kWh or similar.
    * Counters are non-volatile (saved if power fails and restored on powerup)
    * Reported at the global update interval
* Opto-isolated, max 300V between input COM and power supply GND
* Input Voltage Levels
  * Active/ON: 5-24 V between input and common (max 10 mA at 24V input)
  * Inactive/OFF: 0-1.5 V between input and common
* Each common terminal can be high or low side (NPN or PNP type input)
* One common terminal per 4 inputs (1-4, 5-8, 9-12, 13-16)
* Front panel status indicator LED for each channel
## Analogue Inputs
* 8 analogue inputs with software selectable input range/type:
  * 0-20 mA or 4-20 mA (200 ohm load resistance, 24 mA overload max)
  * 0-10 V (1.2 M ohm input resistance, 12 V overload max)
* 0.1 % typical accuracy, 14 bit ADC
* Optional scale/span and offset parameters allow data to be scaled to match sensor (eg 4-20 mA transmitter signal reported as -10…+110 degrees C)
* Sampled at 1 Hz and reported as mean average level over each reporting interval
## Current Transformer Inputs
* 6 inputs for 333 mV type CTs
* Optional scale parameter allows data to be scaled to match sensor (eg 0-100 A)
* Current sampled at 1 Hz and reported as average over each reporting interval
* Frequency of CT4 input sampled at 1 Hz and reported as average over each reporting interval
## Digital Outputs
* 16 open-drain (low side) digital outputs, switching to common ground.
* Max 24 V in OFF state (with inductive load clamp at 50 V, but external clamp diode/snubber recommended)
* Max 500 mA per channel in ON state, with overcurrent and thermal protection
* Suitable for driving small relays/contactors, indicators, actuators.
* Front panel status indicator LED for each channel
* Outputs can be configured to start ON at powerup, or stay OFF until driven on.
## RS-485 Port
* 2-wire, half-duplex RS485 port
* 3.3 V signal level, 5 V tolerant, non-isolated
* Interfaces to 40-way GPIO header SPI bus via SC16IS752 UART bridge.
## Status Display LCD / LEDs
* LED indicator for each digital I/O channel
* Status LEDs (programmable) for system status and processor/disk activity
* LCD text display for user status info
## Processor Module
* Raspberry Pi 2 or 3 (or equivalent) single-board computer with
  * 1 Ethernet LAN port
  * Optional Wireless LAN
  * 4 USB2 ports for expansion I/O or storage
  * Linux operating system running automation software stack with secure remote access to web GUI for configuration, remote control, data reporting etc.
* Connection to Machinon board via standard Raspberry Pi 40-way GPIO header and ribbon cable.
## Miscellaneous
* Real-time clock with battery backup
* Unique serial number / MAC pre-programmed into board
* Global data report interval configurable between 10 secs and 1 day
