# Overview
Example Ruby code for calling the Duo Verify API

## Author
Bryan Kendall
Last Update: 2011-08-12

# Setup

## Configuration
Enter the Duo configuration information for your integration at the top of the 'verify.rb' file. You will need to provide the following information:

- HOST = API hostname
- IKEY = Integration key
- SKEY = Secret key
- PHONE = Your valid phone number to call, in the format of <country code><area code><7 digit number> (in the US, e.g. 15558795309)

## Ruby Gems
This example code uses the following Ruby gems. Please ensure they are installed before you run this script. (use gem install <name>)

- json
- ruby-hmac

# Running
Run the 'verify.rb' file (e.g. from the command line `ruby verify.rb`) after you have entered the information in the Configuration section (above). It will display the status of the call being made, and then ask you for the pin. If you get it wrong, don't worry, it's not going to hurt you. :)

# Need Help?
Please see the Duo Verify documentation:

<http://www.duosecurity.com/docs/duoverify>
