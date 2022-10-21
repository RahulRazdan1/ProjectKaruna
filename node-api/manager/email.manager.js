const { sendSmtpEmail } = require('../services/email.service')
const cases = require('../util/_email.json').cases
const subjectData = require('../util/_email.json').success.subject
const bodyData = require('../util/_email.json').success.emailBody

const sendEmail = async (to, emailFor, username) => {
    try {
        let emailData = { to: to }
        let otpBody
        if (emailFor.toUpperCase() == cases.REGISTER.toUpperCase()) {
            emailData.subject = subjectData.REGISTER
            otpBody = bodyData.REGISTER
        } else if (emailFor.toUpperCase() == cases.LOGIN.toUpperCase()) {
            emailData.subject = subjectData.LOGIN
            otpBody = bodyData.LOGIN
        } else if (emailFor.toUpperCase() == cases.CHANGE_PASSWORD.toUpperCase()) {
            emailData.subject = subjectData.CHANGE_PASSWORD
            otpBody = bodyData.CHANGE_PASSWORD
        }
        otpBody = otpBody.replace('<name>', username)
        emailData.body = otpBody
        const sendOtp = await sendSmtpEmail(emailData)

        return true
    } catch (error) {
        throw error
    }
}

module.exports = { sendEmail }