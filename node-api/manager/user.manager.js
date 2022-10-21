const BadRequestError = require("../_errorHandler/400");
const userModel = require("../models/user.model");
const otpModel = require("../models/otp.model");
const { decryptText, encryptText } = require("../helper/encryptDecrypt");
const UnauthorizedError = require("../_errorHandler/401");
const { sendSms } = require("../services/sms");
const moment = require("moment");
const InternalServerError = require("../_errorHandler/500");
const NotFoundError = require("../_errorHandler/404");
const { ObjectId } = require('mongoose').Types;
const { sendEmail } = require("./email.manager");
const { logger } = require("../_logHandler")

const saveUserData = async (reqBody) => {
	const user = new userModel(reqBody);
	try {
		const userdata = await user.save();

		return userdata
	} catch (error) {
		throw error;
	}
};
const loginUserDB = async (reqBody, loginType) => {
	try {
		logger.info("LOGIN MANAGER CALLED");
		const user = await userModel
			.findOne({
				$or: [{ email: reqBody.username }, { mobile: reqBody.username }],
			})
			.exec();
		if (user) {
            logger.info("user: " + JSON.stringify(user));
			if (loginType == "password") {
				let isPwMatch = await decryptText(reqBody.password, user.password);
				if (!isPwMatch) {
					logger.info("PASSWORD NOT MATCH");
					throw new UnauthorizedError("Wrong credentials");
				} else {
					logger.info("PASSWORD MATCH");
					return user;
				}
			} else if (loginType == "otp") {
				return user;
			} else {
				throw new BadRequestError("Login type is not valid");
			}
		} else {
			throw new UnauthorizedError("Invalid User");
		}
	} catch (error) {
		throw error;
	}
};
const sendOtp = async (mobile, message) => {
	try {
		let sms = await sendSms(mobile, message);
		return sms;
	} catch (error) {
		let err = JSON.parse(JSON.stringify(error));
		err.message = error.message;

		throw new BadRequestError(err);
	}
};

const saveUserOtp = async (userdata, otp) => {
	try {
		let currentTime = new Date(moment());
		let expireTime = new Date(moment().add(10, "m"));
		let userId = userdata._id;
		let otpData = {
			userId: userId,
			otp: otp,
			createdTime: currentTime,
			expireTime: expireTime,
		};
		otpData = new otpModel(otpData);
		return otpData.save();
	} catch (error) {
		console.log(error);
		throw error;
	}
};
const validateOtpDB = async (reqBody, type) => {
	try {
		let otpData = await otpModel.findOne({ _id: reqBody.id }).exec();
		console.log(otpData)
		console.log(reqBody)
		if (otpData) {
			let currentTime = new Date(moment());
			let expireTime = new Date(otpData.expireTime);
			if (currentTime < expireTime) {
				if (reqBody.otp == otpData.otp) {
					if (type == "login") {
						const user = await userModel
							.findOne({ _id: otpData.userId })
							.exec();
						return user;
					} else if (type == "forgetPassword") {
						let pw = reqBody.password;
						let password = await encryptText(pw);
						let doc = await userModel.updateOne(
							{ _id: otpData.userId },
							{ password: password },
							{
								new: true,
							}
						);
						console.log("doc", doc);
						return true;
					} else {
						throw new BadRequestError("type error");
					}
				} else {
					throw new UnauthorizedError("Wrong OTP");
				}
			} else {
				throw new UnauthorizedError("OTP expired");
			}
		}
	} catch (error) {
		console.log(error);
		throw new InternalServerError(error);
	}
};
const resendOtpDB = async (reqBody, type) => {
	try {
		let otpData = await otpModel.findOne({ _id: reqBody.id }).exec();
		console.log(otpData)
		console.log(reqBody)
		if (otpData) {
			const user = await userModel
				.findOne({ _id: otpData.userId })
				.exec();
			return user;
		}
	} catch (error) {
		console.log(error);
		throw new InternalServerError(error);
	}
};
const getUser = async (reqBody) => {
	try {
		let userInfo = await userModel.findById(reqBody).exec();
		if (!userInfo || userInfo == null) {
			throw new NotFoundError("User data not found");
		} else if (userInfo) {
			return userInfo;
		}
	} catch (error) {
		throw new InternalServerError(error);
	}
};
const editUser = async (reqBody, userId) => {
	try {
		if (reqBody.password) {
			let password = reqBody.password;
			delete reqBody.password;
			let pw = await encryptText(password);
			reqBody.password = pw;
		}

		let updatedUser = await userModel
			.updateOne({ _id: userId }, reqBody)
			.exec();
		if (updatedUser) {
			return true;
		} else {
			throw new InternalServerError("Unknown error");
		}
	} catch (error) {
		console.log(error);
		throw new InternalServerError(error);
	}
};

const getAllUserDeviceDetails = async (userType) => {
	try {
		let query = { isActive: true, userType: userType.trim() };
		if (userType.toLowerCase == "all") {
			query = { isActive: true };
		}
		const deviceDetails = await userModel.find(query, {
			deviceType: 1,
			deviceToken: 1,
		});
		return deviceDetails;
	} catch (error) {
		throw error;
	}
};

const getDeviceDetails = async (userData) => {
	try {
		const deviceDetails = await userModel.find({ _id: ObjectId(userData._id), isActive: true }, { deviceType: 1, deviceToken: 1, }).exec()
		return deviceDetails

	} catch (error) {
		throw error
	}
}

const updateDeviceDetails = async (data) => {
	try {
		const deviceType = data.deviceType
		const deviceToken = data.deviceToken
		const userId = data.userId
		const updt = await userModel.updateOne({ _id: ObjectId(userId) }, { deviceType: deviceType, deviceToken: deviceToken, deviceUpdatedAt: new Date }).exec()
		return true
	} catch (error) {
		throw error
	}
}

module.exports = { saveUserData, loginUserDB, sendOtp, saveUserOtp, validateOtpDB, resendOtpDB, getUser, editUser, getAllUserDeviceDetails, updateDeviceDetails, getDeviceDetails };
