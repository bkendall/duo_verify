require 'base64'
require 'openssl'
require 'uri'
require 'net/https'

require 'rubygems'
require 'json'
require 'hmac-sha1'

####### DUO SECURITY SETTINGS #######
# please enter your information here.
# you can find this information in the integrations section
# where you signed up for duo!
# These are REQUIRED to work.
# Please use your valid telephone number, in the format given:
# 	(e.g. 15558675309) (country code, area code, and 7 digit number)
HOST = ""
SKEY = ""
IKEY = ""
PHONE = ""

# Method for creating the Authorization header for our requests to duo
def sign(method, host, path, params)
	canon = [method.upcase, host.downcase, path]
	args = []
	params.sort.each do |k, v|
		args << URI.escape(k, Regexp.new("[^#{URI::PATTERN::UNRESERVED}]")) + '=' + URI.escape(v, Regexp.new("[^#{URI::PATTERN::UNRESERVED}]"))
	end
	canon << args.join("&")
	canon = canon.join("\n")

	hmac = HMAC::SHA1.new(SKEY)
	hmac.update(canon)   

	# This is the string for the Authorization header.
	# Chomps needed to take the \n off that Base64 adds.
	# Broken into a couple pieces because Base64 addes extra \n if it's too long
	"Basic " + Base64.encode64(IKEY + ":").chomp + Base64.encode64(hmac.hexdigest).chomp
end

# uri = full address to connect to, https://.../call
# limit = number of redirects to allow
# method = "POST" or "GET"
# params = the values being sent to the uri
def call_https(uri, limit, method, params)
	uri = URI.parse(uri)

	# Set up the https connection
	http = Net::HTTP.new(uri.host, uri.port)
	http.use_ssl = true
	http.verify_mode = OpenSSL::SSL::VERIFY_NONE

	# Create the 'signature', which is the Authorization header
	# Add the signature to the header, and add params is we are using post
	sig = sign(method, uri.host, uri.path, params)
	if method == "POST"
		request = Net::HTTP::Post.new(uri.request_uri, {"Authorization"=>sig})
		request.set_form_data params
	end
	if method == "GET"
		request = Net::HTTP::Get.new(uri.request_uri, {"Authorization"=>sig})
	end

	# make the call, keep going if we get redirected
	response = http.request(request)
	case response
		when Net::HTTPSuccess then response
		when Net::HTTPRedirection then call_https(response['location'], limit - 1, method, params)
		else nil
	end
end

####### Ruby Duo Verify Script #######

# Create our message, leave <pin> for the system to give the pin.
params = {
	'phone'=>'+'+PHONE,
	'message'=>'This is the ruby duo verify demo. Your pin is: <pin>'
}

# make the https request to make the call
response = call_https("https://#{HOST}/verify/v1/call.json", 10, "POST", params)
# just to make sure it went through, do some tests
if response.nil?
	puts "there was an error in the request"; exit;
end
# we should get our json at this point.
response = JSON.parse(response.body)
if response["stat"] != "OK"
	puts "there was an error with your call\n#{response["message"]}"; exit;
end

# make a record of our pin
pin = response["response"]["pin"]
# set up our next call's parameters with the txid from the previous call
params = {
	'txid' => response["response"]["txid"]
}
status = ""
# limit number of calls we make to check, just in case it goes wild
tries = 10
# make https calls to check the status of our call
while(status != "ended" && tries > 0)
	check = call_https("https://#{HOST}/verify/v1/status.json?txid=#{params["txid"]}", 10, "GET", params)
	if check.nil?
		puts "cannot check your call"; exit;
	end
	# parse and echo our status
	check = JSON.parse(check.body)
	puts check["response"]["info"]
	status = check["response"]["state"]
	tries -= 1
end

# we hope the call didn't actually fail
if check["stat"] == "FAIL"
	puts "Cannot complete. Call failed."
	exit
end

# and just for kicks, tell us what the pin is
puts "What is the pin?"
enter_pin = gets.chomp

puts "Correct!" if enter_pin == pin
puts "False." if enter_pin != pin
