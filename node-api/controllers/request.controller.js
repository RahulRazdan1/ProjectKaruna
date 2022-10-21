const { modifyModel } = require('../helper/modifyModel')
const { sendNotificationToSingleUser } = require('../services/notification.service')
const { addDonate, addRequest, reomveDonateManager, reomveRecieveManager, matchedRequesManager, requestMappinMganager, donationListManager, receiveListManager, donationDetailsManager, receiveDetailsManager, editDonationDetailsManager,
    editReceiveDetailsManager, changedonationStatusManager, changeReceiveStatusManager, matchedRequestManager, getReceiveRequestByRegion, changeRequestStatus, getDoanteRequestByRegion, getUserByReceiveId, getUserByDonationId, acceptReceiveRequest, acceptDonateRequest } = require('../manager/request.manager')

const { failureResponse, successResponse } = require('../services/generateResponse')
const successMsg = require('../util/_message.json').successMessage.donate
const InternalServerError = require('../_errorHandler/500')
const UnauthorizedError = require('../_errorHandler/401')
const BadrequestError = require('../_errorHandler/400')

const requestStatus = require('../util/staticData.json').requestStatus

const makeDonate = async (req, res) => {
    try {
        if (req.user.userType == 'doner') {
            if (req.file) {
                const data = await modifyModel(req)
                data.body.userId = req.user._id
                data.body.image = req.file.filename
                const result = await addDonate(data.body)
                if (req.params.reqId) {
                    let data = {}
                    data.donationId = result._id
                    data.receiveId = req.params.reqId
                    const userdata = await getUserByReceiveId(req.params.reqId)
                    const notificationData = {
                        subject: "You got a donation",
                        message: "Click to view"
                    }
                    await sendNotificationToSingleUser(notificationData, userdata)
                    await requestMappinMganager(data, 'donate')
                }
                successResponse(req, res, result, successMsg.saved)
            } else {
                failureResponse(req, res, new InternalServerError('No file found'))
            }
        } else {
            throw new UnauthorizedError('Only Doner can add the Donate request')
        }

    } catch (error) {
        console.log(error)
        failureResponse(req, res, error)
    }
}

const makeRequest = async (req, res) => {
    try {
        if (req.user.userType == 'receiver') {
            const data = await modifyModel(req)
            data.body.userId = req.user._id
            const result = await addRequest(data.body)
            if (req.params.reqId) {
                let data = {}
                data.receiveId = result._id
                data.donationId = req.params.reqId
                const userdata = await getUserByDonationId(req.params.reqId)
                const notificationData = {
                    subject: "You are requested to donation",
                    message: "Click to view"
                }
                await sendNotificationToSingleUser(notificationData, userdata)
                await requestMappinMganager(data, 'receive')
            }
            successResponse(req, res, result, successMsg.saved)
        } else {
            throw new UnauthorizedError('Only Receiver can add the Recieve request')
        }

    } catch (error) {
        failureResponse(req, res, error)
    }
}

const removeDonate = async (req, res) => {
    try {
        if (req.user.userType == 'doner') {
            const requestId = req.params.requestId
            const data = await modifyModel(req)
            const result = await reomveDonateManager(requestId, data.body)
            successResponse(req, res, result, successMsg.delete)
        } else {
            throw new UnauthorizedError('Only Doner can remove the Donate request')
        }
    } catch (error) {
        failureResponse(req, res, error)
    }
}
const removeReceive = async (req, res) => {
    try {
        if (req.user.userType == 'receiver') {
            const requestId = req.params.requestId
            const data = await modifyModel(req)
            const result = await reomveRecieveManager(requestId, data.body)
            successResponse(req, res, result, successMsg.delete)
        } else {
            throw new UnauthorizedError('Only Receiver can remove the Recieve request')
        }
    } catch (error) {
        failureResponse(req, res, error)
    }
}

const matchingRequest = async (req, res) => {
    try {
        const data = await matchedRequesManager(req.user)
        successResponse(req, res, data, 'found')
    } catch (error) {
        failureResponse(req, res, error)
    }
}

const requestMapping = async (req, res) => {
    try {
        req.body.initiatedBy = req.user._id
        const data = await modifyModel(req)
        const result = await requestMappinMganager(data.body)
        successResponse(req, res, result, 'Request Mapped')
    } catch (error) {
        failureResponse(req, res, error)
    }
}

const donationList = async (req, res) => {
    try {

        let inputs = {}
        inputs.userId = req.params.userId
        inputs.region = req.params.region
        if(req.params.status) {
            let indexOfStatus = requestStatus.findIndex(item => req.params.status.toLowerCase() === item.toLowerCase());
            if (indexOfStatus < 0) {
                throw new BadrequestError('Please give suggested status only')
            }
            inputs.status = requestStatus[indexOfStatus]
        }
        const result = await donationListManager(inputs)
        successResponse(req, res, result, 'Donation List fetched')
    } catch (error) {
        failureResponse(req, res, error)
    }
}

const receiveList = async (req, res) => {
    try {
        let inputs = {}
        inputs.userId = req.params.userId
        inputs.region = req.params.region
        if(req.params.status) {
            let indexOfStatus = requestStatus.findIndex(item => req.params.status.toLowerCase() === item.toLowerCase());
            if (indexOfStatus < 0) {
                throw new BadrequestError('Please give suggested status only')
            }
            inputs.status = requestStatus[indexOfStatus]
        }
        const result = await receiveListManager(inputs)
        successResponse(req, res, result, 'Receive request List fetched')
    } catch (error) {
        failureResponse(req, res, error)
    }
}
const donationDetails = async (req, res) => {
    try {
        let id = req.params.id
        const result = await donationDetailsManager(id)
        successResponse(req, res, result, 'Donation detail found')
    } catch (error) {
        failureResponse(req, res, error)
    }
}
const receiveDetails = async (req, res) => {
    try {
        let id = req.params.id
        const result = await receiveDetailsManager(id)
        successResponse(req, res, result, 'Receive request details found')
    } catch (error) {
        failureResponse(req, res, error)
    }
}
const editDonationDetails = async (req, res) => {
    try {
        let id = req.params.id
        const data = await modifyModel(req)
        const result = await editDonationDetailsManager(id, data.body)
        successResponse(req, res, result, 'Donation detail updated')
    } catch (error) {
        failureResponse(req, res, error)
    }
}
const editReceiveDetails = async (req, res) => {
    try {
        let id = req.params.id
        const data = await modifyModel(req)
        const result = await editReceiveDetailsManager(id, data.body)
        successResponse(req, res, result, 'Receive request details updated')
    } catch (error) {
        failureResponse(req, res, error)
    }
}

const changeDonationStatus = async (req, res) => {
    try {
        let id = req.params.id
        const data = await modifyModel(req)
        // const result = await changedonationStatusManager(id, data.body)
        const payload = {
            donationId: id,
            req: data
        }
        const result = await changeRequestStatus(payload, 'donation')
        successResponse(req, res, result, 'Donation Status Changed')

    } catch (error) {
        failureResponse(req, res, error)
    }
}
const changeReceiveStatus = async (req, res) => {
    try {
        let id = req.params.id
        const data = await modifyModel(req)
        // const result = await changeReceiveStatusManager(id, data.body)
        const payload = {
            receiveId: id,
            req: data
        }
        const result = await changeRequestStatus(payload, 'receive')
        successResponse(req, res, result, 'Receive Status Changed')

    } catch (error) {
        failureResponse(req, res, error)
    }
}

const matchedRequest = async (req, res) => {
    try {
        let inputs = {
            matchId: req.params.matchId,
            region: req.params.region
        }
        const result = await matchedRequestManager(inputs)
        successResponse(req, res, result, 'OK')
    } catch (error) {
        failureResponse(req, res, error)
    }
}

const matchedRequestByRequestId = async (req, res) => {
    try {
        let inputs = {
            requestId: req.params.requestId
        }
        const result = await matchedRequestManager(inputs)
        successResponse(req, res, result, 'OK')
    } catch (error) {
        failureResponse(req, res, error)
    }
}

//25-10-2021 app flow changed

const getRequests = async (req, res) => {
    try {
        const region = req.user.region
        const result = await getReceiveRequestByRegion(region)
        successResponse(req, res, result, 'OK')
    } catch (error) {
        failureResponse(req, res, error)
    }
}
const getDonates = async (req, res) => {
    try {
        const region = req.user.region
        const result = await getDoanteRequestByRegion(region)
        successResponse(req, res, result, 'OK')
    } catch (error) {
        failureResponse(req, res, error)
    }
}

const acceptRequest = async (req, res) => {
    try {
        let receiveId
        let donationId
        const userData = req.user
        let result
        if (req.url.includes('/accept/donation/')) {
            receiveId = req.params.reqId
            const accept = await acceptReceiveRequest(receiveId, userData)
            result = accept
        }
        if (req.url.includes('/accept/receive/')) {
            donationId = req.params.reqId
            const accept = await acceptDonateRequest(donationId, userData)
            result = accept
        }
        successResponse(req, res, result, 'OK')
    } catch (error) {
        failureResponse(req, res, error)
    }
}
module.exports = { makeDonate, makeRequest, removeDonate, removeReceive, matchedRequest, matchedRequestByRequestId, requestMapping, donationList, receiveList, donationDetails, receiveDetails, editDonationDetails, editReceiveDetails, changeDonationStatus, changeReceiveStatus, getRequests, matchingRequest, getDonates, acceptRequest }