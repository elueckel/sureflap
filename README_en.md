# Sureflap Flapdoor
The module allows to read data regarding the flapdoor for pets from Sureflap and to display this data within Symcon. It should be possible to also set status in case it is needed in the future.
Credit goes to: https://github.com/alextoft/sureflap/ ... who did the ground work!!!

## Configuration 1.00
The module requires a sureflap account where flapdoors and pets are setup - please follow the Sureflap instructions in that case. The module itself can be added via Module control or also via Module Store (future). The configuration is as follows

1. Provide Username and Password from Sureflap
2. Click on Initialize to download data for the Hub, Flaps and Pets into Symcon - once this is done, you can download the status of the Flapdoors and Pets. In case new Pets are added, please click on Initialize. 
3. The Update Interval define how often data is downloaded from Sureflap - this can be set with a minimum of 1 minute.


## Version 1.0 (05.01.2021)
* Inquire for Hub and Flapdoors
* Inquire for Pets
* Inquire for Location of Pets (Indoor or Outdoor)
* All data is provided as variables
