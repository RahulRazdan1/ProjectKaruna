const { saveUserData, loginUserDB, sendOtp, saveUserOtp, validateOtpDB, resendOtpDB, getUser, editUser, updateDeviceDetails } = require('../manager/user.manager')
const { generateJwtToken } = require('../services/jwt')
const { successResponse, failureResponse } = require('../services/generateResponse')
const sucessMsg = require('../util/_message.json').successMessage.user

const randomstring = require("randomstring");
const { sendEmail } = require('../manager/email.manager');
const { logger } = require('../_logHandler');

const loginUser = async (req, res) => {

    logger.info("LOGIN CONTROLLER CALLED");

    let type = req.params.type
    if (type == "otp") {
        try {
            logger.info("LOGIN OTP CALLED");
            let user = await loginUserDB(req.body, type)
            let mobile = '+65' + user.mobile
            let userData = JSON.parse(JSON.stringify(user))
            delete userData.password
            delete userData.__v
            let otp = randomstring.generate({
                length: 6,
                charset: 'numeric'
            });
            let message = `OTP to login to your account is ${otp}`

            // mobile = '+91' + '9802275549'

            let sms = await sendOtp(mobile, message)
            if (sms) {
                let otpData = await saveUserOtp(userData, otp)
                let optResp = JSON.parse(JSON.stringify(otpData))
                delete optResp.otp
                delete optResp.__v
                successResponse(req, res, optResp, 'Otp Sent. It is valid upto 10 minuits')
            }
        } catch (error) {
            // console.log(error)
            failureResponse(req, res, error)
        }


    } else if (type == 'password') {
        try {
            logger.info("LOGIN PASSWORD CALLED");
            let user = await loginUserDB(req.body, type)
            let userData = JSON.parse(JSON.stringify(user))
            delete userData.password
            delete userData.__v
            delete userData.deviceToken
            delete userData.deviceType
            let authToken = await generateJwtToken(userData)
            userData.authToken = authToken
            await updateDeviceDetails({
                userId: userData._id,
                deviceType: req.body.deviceType,
                deviceToken: req.body.deviceToken
            })
            successResponse(req, res, userData, sucessMsg.loggedIn)
        } catch (error) {
            failureResponse(req, res, error)
        }
    }
}

const createUser = async (req, res) => {
    try {
        let reqBody = req.body
        let saveUser = await saveUserData(reqBody)
        let responseData = JSON.parse(JSON.stringify(saveUser))
        await sendEmail(responseData.email, 'Register', responseData.firstName)
        delete responseData.password
        delete responseData.userId
        delete responseData.__v
        successResponse(req, res, responseData, sucessMsg.saved)
    } catch (error) {
        failureResponse(req, res, error)
    }
}

const forgetPassword = async (req, res) => {
    try {
        let user = await loginUserDB(req.body, 'otp')
        let mobile = '+65' + user.mobile
        let userData = JSON.parse(JSON.stringify(user))
        delete userData.password
        delete userData.__v
        let otp = randomstring.generate({
            length: 6,
            charset: 'numeric'
        });
        let message = `OTP to login to your account is ${otp}`

        // mobile = '+91' + '9802275549'

        let sms = await sendOtp(mobile, message)
        if (sms) {
            let otpData = await saveUserOtp(userData, otp)
            let optResp = JSON.parse(JSON.stringify(otpData))
            delete optResp.otp
            delete optResp.__v
            successResponse(req, res, optResp, 'Otp Sent. It is valid upto 10 minuits')
        }
    } catch (error) {
        console.log(error)
        failureResponse(req, res, error)
    }
}

const validateOtp = async (req, res) => {
    try {
        let user = await validateOtpDB(req.body, 'login')
        let userData = JSON.parse(JSON.stringify(user))
        delete userData.password
        delete userData.__v
        let authToken = await generateJwtToken(userData)
        await updateDeviceDetails({
            userId: userData._id,
            deviceType: req.body.deviceType,
            deviceToken: req.body.deviceToken
        })
        userData.authToken = authToken
        successResponse(req, res, userData, sucessMsg.loggedIn)

    } catch (error) {
        failureResponse(req, res, error)
    }

}

const resendOtp = async (req, res) => {
    try {
        let user = await resendOtpDB( req.body )
        let userData = JSON.parse(JSON.stringify(user))

        let mobile = '+65' + user.mobile
        delete userData.password
        delete userData.__v
        let otp = randomstring.generate({
            length: 6,
            charset: 'numeric'
        });
        let message = `OTP to login to your account is ${otp}`

        // mobile = '+91' + '8307048451'

        let sms = await sendOtp(mobile, message)
        if (sms) {
            let otpData = await saveUserOtp(userData, otp)
            let optResp = JSON.parse(JSON.stringify(otpData))
            delete optResp.otp
            delete optResp.__v
            successResponse(req, res, optResp, 'Otp Sent. It is valid upto 10 minuits')
        }

    } catch (error) {
        failureResponse(req, res, error)
    }

}
const validateOtpForChangePw = async (req, res) => {
    try {
        let user = await validateOtpDB(req.body, 'forgetPassword')
        successResponse(req, res, user, sucessMsg.passwordChanged)

    } catch (error) {
        console.log(error)
        failureResponse(req, res, error)
    }

}

const getProfile = async (req, res) => {
    try {
        let user = await getUser(req.params.id)
        user = JSON.parse(JSON.stringify(user))
        delete user.password
        delete user.__v
        console.log(user)
        successResponse(req, res, user, sucessMsg.found)
    } catch (error) {
        console.log(error)
        failureResponse(req, res, error)
    }
}

const editProfile = async (req, res) => {
    try {
        let userId = req.params.id
        let updatedUserInfo = await editUser(req.body, userId)
        successResponse(req, res, updatedUserInfo, sucessMsg.updated)
    } catch (error) {
        console.log(error)
        failureResponse(req, res, error)
    }
}

module.exports = { loginUser, createUser, forgetPassword, validateOtp, resendOtp, validateOtpForChangePw, getProfile, editProfile }