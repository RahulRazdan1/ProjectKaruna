const express = require('express')
const { createUser, loginUser, validateOtp, resendOtp, validateOtpForChangePw, getProfile, editProfile, forgetPassword } = require('../controllers/user.controller')
const { checkValidationResult } = require('../validator/expressvalidator')
const { createUserValidate, updateUserValidate } = require('../validator/user.validator')
const { authentication } = require('../services/auth.service')
const router = express.Router()

router.post('/signup', createUserValidate(), checkValidationResult, createUser)
router.post('/login/:type', loginUser)
router.post('/forgetPassword', forgetPassword)
router.post('/validateOtp', validateOtp)
router.post('/resendOtp', resendOtp)
router.post('/changePassword', validateOtpForChangePw)
router.get('/getProfile/:id', authentication, getProfile)
router.post('/editProfile/:id', authentication, updateUserValidate(), checkValidationResult, editProfile)

module.exports = router