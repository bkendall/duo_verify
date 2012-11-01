# Overview
Example Ruby code for calling the Duo Verify API

## Author
Bryan Kendall

# Setup

## Configuration
Enter the Duo configuration information for your integration at the top of the `duo_verify.rb` file. You will need to provide the following information:

- `HOST` = API hostname
- `IKEY` = Integration key
- `SKEY` = Secret key
- `PHONE` = Your valid phone number to call (e.g. 555-879-5309)

## Ruby Gems
This example code uses the following Ruby gems. Please ensure they are installed before you run this script. (use `gem install <name>`)

- `json`

# Running
Run `duo_verify.rb` (e.g. from the command line `ruby duo_verify.rb`) after you have entered the information in the Configuration section (above). It will display the status of the call being made, and then ask you for the pin. If you get it wrong, don't worry, it's not going to hurt you. :)

# Need Help?
Please see the Duo Verify documentation:

<http://www.duosecurity.com/docs/duoverify>
