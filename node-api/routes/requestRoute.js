const express = require('express')
const { makeDonate, makeRequest, removeDonate, removeReceive, matchedRequest, matchedRequestByRequestId, requestMapping, donationList, receiveList, donationDetails, receiveDetails, editDonationDetails, editReceiveDetails, changeDonationStatus, changeReceiveStatus, getRequests, matchingRequest, getDonates, acceptRequest } = require('../controllers/request.controller')

const { authentication, volunteerAuth } = require('../services/auth.service')
const { fileUpload } = require('../services/fileUpload')
const { checkValidationResult } = require('../validator/expressvalidator')
const { validateDonateInputs, validateDeleteDonate, validateDeleteRecieve, validateDonationUser, validateReceiverUser, validatedonationId, validateReceiveId, validateDonationStatus, validateReceiveStatus, validateSendRequest } = require('../validator/request.validator')

const router = express.Router()

router.post('/donate', fileUpload('image', 'donate'), validateDonateInputs(), checkValidationResult, authentication, makeDonate)
router.post('/donate/:reqId', fileUpload('image', 'donate'), validateDonateInputs(), checkValidationResult, authentication, makeDonate)

router.post('/receive', validateDonateInputs(), checkValidationResult, authentication, makeRequest)
router.post('/receive/:reqId', validateDonateInputs(), checkValidationResult, authentication, makeRequest)

router.get('/removeDonate/:requestId', validateDeleteDonate(), checkValidationResult, authentication, removeDonate)
router.get('/removeReceive/:requestId', validateDeleteRecieve(), checkValidationResult, authentication, removeReceive)

router.get('/matched', authentication, matchingRequest)
router.post('/sendRequest', authentication, validateSendRequest(), checkValidationResult, requestMapping)

router.get('/donationList/:userId', validateDonationUser(), checkValidationResult, authentication, donationList)
router.get('/donationList/:userId/:status', validateDonationUser(), checkValidationResult, authentication, donationList)
router.get('/donationList', authentication, donationList)
router.get('/donationListByStatus/:status', authentication, donationList)
router.get('/donationListByRegion/:region', authentication, donationList)//ok

router.get('/receiveList/:userId', validateReceiverUser(), checkValidationResult, authentication, receiveList)
router.get('/receiveList/:userId/:status', validateReceiverUser(), checkValidationResult, authentication, receiveList)
router.get('/receiveList', authentication, receiveList)
router.get('/receiveListByStatus/:status', authentication, receiveList)//ok
router.get('/receiveListByRegion/:region', authentication, receiveList)//ok

router.get('/donationDetails/:id', validatedonationId(), checkValidationResult, authentication, donationDetails)
router.get('/receiveDetails/:id', validateReceiveId(), checkValidationResult, authentication, receiveDetails)

router.post('/editDonationDetails/:id', fileUpload('image', 'donate'), authentication, editDonationDetails)
router.post('/editReceiveDetails/:id', authentication, editReceiveDetails)

router.post('/changeDonationStatus/:id', validateDonationStatus(), checkValidationResult, authentication, changeDonationStatus)
router.post('/changeReceiveStatus/:id', validateReceiveStatus(), checkValidationResult, authentication, changeReceiveStatus)

// router.get('/adminDashboard', authentication, volunteerAuth, matchedRegionRequest)//pending category subcategory count donat
// router.get('/volunteerDashboard', authentication, volunteerAuth, matchedRegionRequest)//ok

router.get('/matchedRequestByRegion/:region', authentication, volunteerAuth, matchedRequest)//ok
router.get('/matchedRequest', authentication, volunteerAuth, matchedRequest)//ok
router.get('/matchedRequestDetails/:matchId', authentication, volunteerAuth, matchedRequest)//ok name, address, region, phone
router.get('/matchedRequestDetailsByRequestId/:requestId', authentication, matchedRequestByRequestId)//ok name, address, region, phone

//new endpoints after 25-10-2021  [app flow changed]

router.get('/getRequests', authentication, getRequests)
router.get('/getDonates', authentication, getDonates)

router.get('/accept/donation/:reqId', authentication, acceptRequest)
router.get('/accept/receive/:reqId', authentication, acceptRequest)

module.exports = router