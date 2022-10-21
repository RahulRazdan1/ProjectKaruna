const appConfig = require("../config/appConfig.json");

const twilioConfig = appConfig.twilioConfig;
const accountSid = twilioConfig.ACCOUNT_SID;
const authToken = twilioConfig.AUTH_TOKEN;
const twilioPhoneNo = twilioConfig.PHONE;
const client = require("twilio")(accountSid, authToken);

const sendSms = (phone, message) =>
	new Promise((resolve, reject) => {
		client.messages
			.create({
				body: message,
				from: twilioPhoneNo,
				to: phone,
			})
			.then((message) => resolve(message))
			.catch((error) => {
				reject(error);
				// throw error
			});
	});

module.exports = { sendSms };
