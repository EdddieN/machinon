# Hardware Description
## GPIO Header
The Machinon board is designed to interface with a Raspberry Pi 2 or Pi 3 (or equivalent) via the 40-pin GPIO header. Pins used by the Machinon are shown in the table below. Unlisted pins are not used.

| Pin | Name | Machinon Function |
| --- | ---- | ----------------- |
| 1   | 3.3V | Not currently used |
| 2   | 5V   | 5V supply from Machinon regulator (max 2A total) |
| 3   | BCM2 SDA | RTC I2C data |
| 4   | 5V   | 5V supply from Machinon regulator |
| 5   | BCM3 SCL | RTC I2C clock |
| 6   | Ground | Power ground |
| 8   | BCM14 TXD | Serial data from R-Pi to Machinon data port |
| 9   | Ground | Power ground |
| 10  | BCM15 RXD | Serial data to R-Pi from Machinon data port |
| 12  | BCM18 SPI1 CE0 | SC16IS752 /CS (chip select) |
| 14  | Ground | Power ground |
| 16  | BCM23 | Machinon main microcontroller reset. A high pulse of approx 10 ms on this pin resets the main MCU. |
| 18  | BCM24 | SC16IS752 /IRQ (data received interrupt) |
| 20  | Ground | Power ground |
| 25  | Ground | Power ground |
| 27  | BCM0 ID_SDA | R-Pi ID EEPROM SDA |
| 28  | BCM1 ID_SCL | R-Pi ID EEPROM SCL |
| 30  | Ground | Power ground |
| 33  | BCM13 | Front panel green LED D5 (active high) |
| 34  | Ground | Power ground |
| 35  | BCM19 SPI1 MISO | SC16IS752 MISO |
| 37  | BCM26 | Front panel red LED D4 (used for R-Pi ACT LED) |
| 38  | BCM20 SPI1 MOSI | SC16IS752 MOSI |
| 39  | Ground | Power Ground |
| 40  | BCM21 SPI1 SCLK | SC16IS752 SCLK

## RTC
The Machinon board features a Microchip MCP7941x real-time clock with battery backup (requires CR2016 or CR2032 coin cell). This is used by the Raspberry Pi for time-of-day when there is no network connection. The Raspberry Pi must be configured to use the RTC - see software setup section.

## SPI-UART
The Machinon board features a SC16IS752 2-channel SPI to UART expander to give the Raspberry Pi additional serial ports. Channel A is used for the RS-485 interface, and channel B is used for MySensors config messages. The Raspberry pi must be configured to use the SC16IS752 - see software setup section.

## Digital Inputs
The 16 digital input channels are opto-isolated and can operate with either polarity, ie each input can be positive or negative with respect to its common terminal. There are 4 common terminals, each serving 4 input channels (ie DIN1-DIN4 share a common terminal, DIN5-DIN8 share another common terminal etc.). The inputs are voltage-driven (not current loop).

The digital inputs can operate either as binary status (logic level) inputs or as cumulative pulse counters. Both modes have fixed debounce timing, which determines the minimum pulse width and maximum counter frequency (see specification table).

The pulse counters are 32-bit non-resetting and non-volatile, so the count is not lost at poweroff or reset. The counters can individually be reset with a special command. The counter values can be scaled and offset with user-programmed parameters.

The digital inputs are handled with a dedicated slave microcontroller and EEPROM for reliable sensing/counting.

## Analogue Inputs
The analogue input channels are sampled every second, and the reported values are the mean average input level over each reporting interval.

Each input channel can be programmed to work in 0-10 V mode or 0-20/4-20 mA mode, and can be set to report as several MySensors sensor/data types, The values can be scaled and offset with user-programmed parameters.

## Current Transformer Inputs
The current transformer (CT) inputs use a dedicated measurement IC to measure rms current from 333 mV type current transformers. The CT4 channel also measures the frequency of the current if the current is sufficient for noise-free frequency detection.

Each input can be set to report as current or power, and the values can be scaled with a user-programmed parameter to account for CT ratio and (for power) the assumed voltage and power factor.

## Digital Outputs
The digital outputs are open-drain types (switch to ground) that can drive small relays/contactors, LED indicators or similar low-current loads (see specification table).

If a large contactor or high-current load is to be driven, a separate low-current relay or driver module may be required to handle the load current.

## Display Panel
The front panel display includes LED indicators for each digital input and output, plus several user-programmable status indicators and a dot-matrix LCD panel.

The status LEDs include:
* Status LED 1 (green/red) is normally ON and blinks off each second to indicate sampling and "heartbeat". The colour can be programmed to green or red using MySensors commands.
* Status LED 2 (green) can be programmed ON or OFF using MySensors commands.
* Raspberry Pi ACT LED (red) connected to GPIO26 (pin 37) and used with ACT LED overlay to show SD card access/activity.
* Raspberry Pi additional LED (green) connected to GPIO13 (pin 33), available for user application.

The LCD is a dot-matrix type arranged as 7 lines of 21 characters. At powerup/reset, the Machinon displays firmware version and serial number info. User-specified text strings can be written on each line using MySensors commands.
