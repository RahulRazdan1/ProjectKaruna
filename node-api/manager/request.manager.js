const Donate = require('../models/donateRequest.model')
const Receive = require('../models/receiveRequest.model')
const User = require('../models/user.model')
const InternalServerError = require('../_errorHandler/500')
const UnauthorizedError = require('../_errorHandler/401')
const NotFoundError = require('../_errorHandler/404')
const BadrequestError = require('../_errorHandler/400')
const { ForbiddenError } = require('../_errorHandler/error')
const { ObjectId } = require('mongoose').Types

const fileConfig = require('../config/fileConfig.json')

const RequestMap = require('../models/requestMap.model')

const requestStatus = require('../util/staticData.json').requestStatus

const { sendNotificationToSingleUser } = require('../services/notification.service')

const addDonate = async (reqBody) => {
    try {
        const donate = new Donate(reqBody)
        return donate.save()
    } catch (error) {
        throw new InternalServerError(error)
    }
}

const addRequest = async (reqBody) => {
    try {
        const receive = new Receive(reqBody)
        return receive.save()
    } catch (error) {
        throw new InternalServerError(error)
    }
}

const reomveDonateManager = async (requestId, reqBody) => {
    try {
        reqBody.isActive = false
        const record = await Donate.findByIdAndUpdate(requestId, reqBody, { new: true }).exec()
        return true
    } catch (error) {
        throw new InternalServerError(error)
    }
}

const reomveRecieveManager = async (requestId, reqBody) => {
    try {
        reqBody.isActive = false
        const record = await Receive.findByIdAndUpdate(requestId, reqBody, { new: true }).exec()
        return true
    } catch (error) {
        throw new InternalServerError(error)
    }
}

const matchedRequesManager = async (userdata) => {
    try {
        const userId = new ObjectId(userdata._id)

        let fromCol = ""
        if (userdata.userType == "doner") {
            fromCol = "donates"
        } else if (userdata.userType == 'receiver') {
            fromCol = "receives"
        }
        let query = [
            {
                $match: {
                    userId: userId,
                    isActive: true
                },
            },
            {
                $lookup: {
                    from: fromCol,
                    localField: 'subcategoryId',
                    foreignField: 'subcategoryId',
                    as: 'matchedRequest'
                }
            }
        ]
        console.log(query)
        if (userdata.userType == "doner") {
            const donateData = await Donate.aggregate(query).exec()
            return donateData
        } else if (userdata.userType == 'receiver') {
            const result = await Donate.aggregate(query).exec()
            return result
        } else {
            throw new UnauthorizedError('You are not autherized to perform this action')
        }
    } catch (error) {
        throw error
    }
}

const requestMappinMganager = async (reqBody, flag) => {
    try {
        const records = await RequestMap.count({ donationId: ObjectId(reqBody.donationId), receiveId: ObjectId(reqBody.receiveId), isActive: true })
        if (records > 0) {
            throw new ForbiddenError('Already matched')
        }
        const donerRegion = await Donate.findOne({ _id: ObjectId(reqBody.donationId), isActive: true }, { region: 1, createdBy: 1, _id: 0 }).exec()
        const receiveRegion = await Receive.findOne({ _id: ObjectId(reqBody.receiveId), isActive: true }, { region: 1, createdBy: 1, _id: 0 }).exec()
        // if (donerRegion.region == receiveRegion.region) {
        //     reqBody.region = donerRegion.region
            if (flag == "donate") {
                reqBody.region = donerRegion.region
                reqBody.initiatedBy = donerRegion.createdBy
            }
            if (flag == "receive") {
                reqBody.region = receiveRegion.region
                reqBody.initiatedBy = receiveRegion.createdBy
            }

        // } else {
        //     throw new ForbiddenError('Region mismatched')
        // }
        reqBody.status = requestStatus[2]
        console.log(reqBody)
        const mapdata = new RequestMap(reqBody)
        console.log(mapdata)
        const result = await mapdata.save()
        return result
    } catch (error) {
        throw error
    }
}

const donationListManager = async (reqBody) => {
    try {
        let query = { isActive: true }
        if (reqBody.userId && reqBody.userId !== null) {
            query.userId = ObjectId(reqBody.userId)
        }
        if (reqBody.region && reqBody.region !== null) {
            query.region = reqBody.region
        }
        if (reqBody.status && reqBody.status !== null) {
            query.status = reqBody.status
        }
        // const donates = await Donate.find(query).exec()
        const aggrArr = [
            {
                $match: query
            },
            {
                $lookup: {
                    from: 'categories',
                    localField: "categoryId",
                    foreignField: '_id',
                    as: "categoryDetails"
                }
            },
            { $unwind: "$categoryDetails" },
            {
                $lookup: {
                    from: 'categories',
                    localField: "subcategoryId",
                    foreignField: '_id',
                    as: "subCategoryDetails"
                }
            },
            { $unwind: "$subCategoryDetails" },
            {
                $addFields: {
                    categoryName: '$categoryDetails.name',
                    subCategoryName: '$subCategoryDetails.name'
                }
            },
            {
                $addFields: {
                    newCreatedAt: {
                        date: { $dateToString: { format: "%m/%d/%Y ,", date: "$cretedAt", timezone: "GMT" }},
                        hour: { $dateToString: { format: "%H", date: "$cretedAt", timezone: "GMT" }},
                        time: { $dateToString: { format: ":%M:%S", date: "$cretedAt", timezone: "GMT" }}
                    }
                }
            },
            {
                $set: {
                    "newCreatedAt.hour": {
                        $cond: {
                            "if": { $gt: [ { "$toInt": "$newCreatedAt.hour" }, 11 ] },
                            "then": {
                                "$concat": [
                                    {
                                        $cond: [
                                            { $eq: [{  "$toInt": "$newCreatedAt.hour"},12 ] },
                                            "12",
                                            { $toString: {"$subtract": [ { "$toInt": "$newCreatedAt.hour"  }, 12 ] }}
                                        ] 
                                    },
                                    " PM" ] 
                                },
                            "else": { "$concat": [ "$newCreatedAt.hour", " AM" ] }
                        }
                    }
                }
            },
            {
                $set: {
                    "newCreatedAt": {
                        "$concat": [
                        "$newCreatedAt.date",
                        { "$arrayElemAt": [{ "$split": [ "$newCreatedAt.hour", " " ] }, 0 ] },
                        "$newCreatedAt.time",
                        " ",
                        { "$arrayElemAt": [{ "$split": [ "$newCreatedAt.hour", " " ] }, 1 ] }
                        ]
                    }
                }
            },
            {
                $project: {
                    "userId": 1,
                    "categoryId": 1,
                    "subcategoryId": 1,
                    'categoryName': 1,
                    'subCategoryName': 1,
                    "description": 1,
                    "region": 1,
                    "deliveryType": 1,
                    "address": 1,
                    "cretedAt": "$newCreatedAt",
                    "createdBy": 1,
                    "modifiedAt": 1,
                    "modifiedBy": 1,
                    "status": 1,
                }
            }

        ]
        const donates = await Donate.aggregate(aggrArr).exec()
        return donates
    } catch (error) {
        throw error
    }
}

const receiveListManager = async (reqBody) => {
    try {
        let query = { isActive: true }
        if (reqBody.userId && reqBody.userId !== null) {
            query.userId = ObjectId(reqBody.userId)
        }
        if (reqBody.region && reqBody.region !== null) {
            query.region = reqBody.region
        }
        if (reqBody.status && reqBody.status !== null) {
            query.status = reqBody.status
        }

        const aggrArr = [
            {
                $match: query
            },
            {
                $lookup: {
                    from: 'categories',
                    localField: "categoryId",
                    foreignField: '_id',
                    as: "categoryDetails"
                }
            },
            { $unwind: "$categoryDetails" },
            {
                $lookup: {
                    from: 'categories',
                    localField: "subcategoryId",
                    foreignField: '_id',
                    as: "subCategoryDetails"
                }
            },
            { $unwind: "$subCategoryDetails" },
            {
                $addFields: {
                    categoryName: '$categoryDetails.name',
                    subCategoryName: '$subCategoryDetails.name'
                }
            },
            {
                $addFields: {
                    newCreatedAt: {
                        date: { $dateToString: { format: "%m/%d/%Y ,", date: "$cretedAt", timezone: "GMT" }},
                        hour: { $dateToString: { format: "%H", date: "$cretedAt", timezone: "GMT" }},
                        time: { $dateToString: { format: ":%M:%S", date: "$cretedAt", timezone: "GMT" }}
                    }
                }
            },
            {
                $set: {
                    "newCreatedAt.hour": {
                        $cond: {
                            "if": { $gt: [ { "$toInt": "$newCreatedAt.hour" }, 11 ] },
                            "then": {
                                "$concat": [
                                    {
                                        $cond: [
                                            { $eq: [{  "$toInt": "$newCreatedAt.hour"},12 ] },
                                            "12",
                                            { $toString: {"$subtract": [ { "$toInt": "$newCreatedAt.hour"  }, 12 ] }}
                                        ] 
                                    },
                                    " PM" ] 
                                },
                            "else": { "$concat": [ "$newCreatedAt.hour", " AM" ] }
                        }
                    }
                }
            },
            {
                $set: {
                    "newCreatedAt": {
                        "$concat": [
                        "$newCreatedAt.date",
                        { "$arrayElemAt": [{ "$split": [ "$newCreatedAt.hour", " " ] }, 0 ] },
                        "$newCreatedAt.time",
                        " ",
                        { "$arrayElemAt": [{ "$split": [ "$newCreatedAt.hour", " " ] }, 1 ] }
                        ]
                    }
                }
            },
            {
                $project: {
                    "userId": 1,
                    "categoryId": 1,
                    "subcategoryId": 1,
                    'categoryName': 1,
                    'subCategoryName': 1,
                    "description": 1,
                    "region": 1,
                    "deliveryType": 1,
                    "address": 1,
                    "cretedAt": "$newCreatedAt",
                    "createdBy": 1,
                    "modifiedAt": 1,
                    "modifiedBy": 1,
                    "status": 1,
                }
            }

        ]
        const result = await Receive.aggregate(aggrArr).exec()
        return result
    } catch (error) {
        throw error
    }
}

const donationDetailsManager = async (id) => {
    try {
        let query = {
            $and: [
                { _id: ObjectId(id) }, { isActive: true }
            ]
        }
        const aggrArr = [
            {
                $match: query
            },
            {
                $lookup: {
                    from: 'categories',
                    localField: "categoryId",
                    foreignField: '_id',
                    as: "categoryDetails"
                }
            },
            { $unwind: "$categoryDetails" },
            {
                $lookup: {
                    from: 'categories',
                    localField: "subcategoryId",
                    foreignField: '_id',
                    as: "subCategoryDetails"
                }
            },
            { $unwind: "$subCategoryDetails" },
            {
                $addFields: {
                    categoryName: '$categoryDetails.name',
                    subCategoryName: '$subCategoryDetails.name'
                }
            },
            {
                $addFields: {
                    newCreatedAt: {
                        date: { $dateToString: { format: "%m/%d/%Y ,", date: "$cretedAt", timezone: "GMT" }},
                        hour: { $dateToString: { format: "%H", date: "$cretedAt", timezone: "GMT" }},
                        time: { $dateToString: { format: ":%M:%S", date: "$cretedAt", timezone: "GMT" }}
                    }
                }
            },
            {
                $set: {
                    "newCreatedAt.hour": {
                        $cond: {
                            "if": { $gt: [ { "$toInt": "$newCreatedAt.hour" }, 11 ] },
                            "then": {
                                "$concat": [
                                    {
                                        $cond: [
                                            { $eq: [{  "$toInt": "$newCreatedAt.hour"},12 ] },
                                            "12",
                                            { $toString: {"$subtract": [ { "$toInt": "$newCreatedAt.hour"  }, 12 ] }}
                                        ] 
                                    },
                                    " PM" ] 
                                },
                            "else": { "$concat": [ "$newCreatedAt.hour", " AM" ] }
                        }
                    }
                }
            },
            {
                $set: {
                    "newCreatedAt": {
                        "$concat": [
                        "$newCreatedAt.date",
                        { "$arrayElemAt": [{ "$split": [ "$newCreatedAt.hour", " " ] }, 0 ] },
                        "$newCreatedAt.time",
                        " ",
                        { "$arrayElemAt": [{ "$split": [ "$newCreatedAt.hour", " " ] }, 1 ] }
                        ]
                    }
                }
            },
            {
                $project: {
                    "userId": 1,
                    "categoryId": 1,
                    "subcategoryId": 1,
                    'categoryName': 1,
                    'subCategoryName': 1,
                    'image': 1,
                    "description": 1,
                    "region": 1,
                    "deliveryType": 1,
                    "address": 1,
                    "cretedAt": "$newCreatedAt",
                    "createdBy": 1,
                    "modifiedAt": 1,
                    "modifiedBy": 1,
                    "status": 1,
                }
            }

        ]
        const donates = await Donate.aggregate(aggrArr).exec()
        if (donates.length <= 0) {
            throw new NotFoundError('No details found with this request')
        }
        donates[0].image = fileConfig.donationImage.path + '/' + donates[0].image
        return donates[0]
    } catch (error) {
        throw error
    }
}

const receiveDetailsManager = async (id) => {
    try {
        let query = { _id: ObjectId(id), isActive: true }

        const aggrArr = [
            {
                $match: query
            },
            {
                $lookup: {
                    from: 'categories',
                    localField: "categoryId",
                    foreignField: '_id',
                    as: "categoryDetails"
                }
            },
            { $unwind: "$categoryDetails" },
            {
                $lookup: {
                    from: 'categories',
                    localField: "subcategoryId",
                    foreignField: '_id',
                    as: "subCategoryDetails"
                }
            },
            { $unwind: "$subCategoryDetails" },
            {
                $addFields: {
                    categoryName: '$categoryDetails.name',
                    subCategoryName: '$subCategoryDetails.name'
                }
            },
            {
                $project: {
                    "userId": 1,
                    "categoryId": 1,
                    "subcategoryId": 1,
                    'categoryName': 1,
                    'subCategoryName': 1,
                    "description": 1,
                    "region": 1,
                    "deliveryType": 1,
                    "address": 1,
                    "cretedAt": 1,
                    "createdBy": 1,
                    "modifiedAt": 1,
                    "modifiedBy": 1,
                    "status": 1,
                }
            }

        ]
        const receives = await Receive.aggregate(aggrArr).exec()
        if (receives.length <= 0) {
            throw new NotFoundError('No details found with this request')
        }
        return receives[0]
    } catch (error) {
        throw error
    }
}

const editDonationDetailsManager = async (id, reqBody) => {
    try {

        const update = await Donate.updateOne({ _id: ObjectId(id) }, reqBody, { new: true }).exec()
        console.log(update)
        return true
    } catch (error) {
        throw error
    }
}

const editReceiveDetailsManager = async (id, reqBody) => {
    try {
        const update = await Receive.updateOne({ _id: ObjectId(id) }, reqBody, { new: true }).exec()
        return true
    } catch (error) {
        throw error
    }
}

const changedonationStatusManager = async (id, reqBody) => {
    try {
        const result = await editDonationDetailsManager(id, reqBody)
        return result
    } catch (error) {
        throw error
    }
}

const changeReceiveStatusManager = async (id, reqBody) => {
    try {
        const result = await editReceiveDetailsManager(id, reqBody)
        return result
    } catch (error) {
        throw error
    }
}

const matchedRequestManager = async (reqBody) => {
    try {
        let query = { isActive: true }
        if (reqBody.matchId && reqBody.matchId !== null) {
            query = { _id: ObjectId(reqBody.matchId), isActive: true }
        }
        if (reqBody.region && reqBody.region !== null) {
            query = { region: reqBody.region, isActive: true }
        }
        if (reqBody.requestId && reqBody.requestId !== null) {
            query = { 
				$or: [{ donationId: ObjectId(reqBody.requestId) }, { receiveId: ObjectId(reqBody.requestId) }],
                isActive: true 
            }
        }


        const aggrArr = [
            {
                $match: query
            },
            {
                $lookup: {
                    from: 'donates',
                    localField: "donationId",
                    foreignField: '_id',
                    as: 'donationDetails',
                },

            },
            { $unwind: "$donationDetails" },
            {
                $lookup: {
                    from: 'categories',
                    localField: "donationDetails.categoryId",
                    foreignField: '_id',
                    as: 'categoryDetails',
                },

            },
            { $unwind: "$categoryDetails" },
            {
                $lookup: {
                    from: 'categories',
                    localField: "donationDetails.subcategoryId",
                    foreignField: '_id',
                    as: 'subcategoryDetails',
                },

            },
            { $unwind: "$subcategoryDetails" },
            {
                $lookup: {
                    from: 'receives',
                    localField: "receiveId",
                    foreignField: '_id',
                    as: 'receiveDetails'
                }
            },
            { $unwind: "$receiveDetails" },
            {
                $lookup: {
                    from: 'users',
                    localField: "donationDetails.userId",
                    foreignField: '_id',
                    as: 'donerDetails'
                }
            },
            { $unwind: "$donerDetails" },
            {
                $lookup: {
                    from: 'users',
                    localField: "receiveDetails.userId",
                    foreignField: '_id',
                    as: 'receiverDetails'
                }
            },
            { $unwind: "$receiverDetails" },
            {
                $addFields: {
                    "categoryId": "$donationDetails.categoryId",
                    "categoryName": "$donationDetails._id",
                    "subCategoryId": "$donationDetails.subcategoryId",
                    "subCategoryId": "$donationDetails._id"
                }
            },
            {
                $project: {
                    region: 1,
                    donationId: 1,
                    receiveId: 1,
                    initiatedBy: 1,
                    "donerDetails._id": 1,
                    "donerDetails.firstName": 1,
                    "donerDetails.lastName": 1,
                    "donerDetails.email": 1,
                    "donerDetails.mobile": 1,
                    "donerDetails.address": 1,
                    "donerDetails.region": 1,
                    "receiverDetails._id": 1,
                    "receiverDetails.firstName": 1,
                    "receiverDetails.lastName": 1,
                    "receiverDetails.email": 1,
                    "receiverDetails.mobile": 1,
                    "receiverDetails.address": 1,
                    "receiverDetails.region": 1,
                    "categoryDetails._id": 1,
                    "categoryDetails.name": 1,
                    "subcategoryDetails._id": 1,
                    "subcategoryDetails.name": 1,

                }
            }
        ]
        // const result = await RequestMap.find().exec()
        const result = await RequestMap.aggregate(aggrArr).exec()

        return result
    } catch (error) {
        throw error
    }
}

const changeRequestStatus = async (data, owner) => {
    try {
        let donationId, receiveId, mappedReq
        let indexOfStatus = requestStatus.findIndex(item => data.req.body.status.toLowerCase() === item.toLowerCase());
        if (indexOfStatus < 0) {
            throw new BadrequestError('Please give suggested status only')
        }
        console.log(data.req.body)
        console.log(owner)
        if (owner == 'donation') {
            donationId = data.donationId
            mappedReq = await RequestMap.find({ donationId: ObjectId(donationId), isActive: true })
        }
        if (owner == 'receive') {
            receiveId = data.receiveId
            mappedReq = await RequestMap.find({ receiveId: ObjectId(receiveId), isActive: true })
        }
        if (mappedReq.length > 0) {
            mappedReq = mappedReq[0]
            if (data.req.body.status.toLowerCase() == requestStatus[2].toLowerCase()) {
                throw ForbiddenError('You can not set status as Matched')
            }
            else {
                await RequestMap.updateOne({ _id: ObjectId(mappedReq._id) }, { status: requestStatus[indexOfStatus], isActive: false }).exec()
                await Donate.updateOne({ _id: ObjectId(mappedReq.donationId) }, { status: requestStatus[indexOfStatus] }).exec()
                await Receive.updateOne({ _id: ObjectId(mappedReq.receiveId) }, { status: requestStatus[indexOfStatus] }).exec()

                let notificationDonorData, notificationReceiverData
                if (owner == 'donation') {
                    if(indexOfStatus == 0){
                        notificationDonorData = {
                            subject: "You have rejected Donation Offer",
                            message: "Click to view"
                        }
                        notificationReceiverData = {
                            subject: "Donation Request has been Rejected",
                            message: "Click to view"
                        }
                    }
                    if(indexOfStatus == 1){
                        notificationDonorData = {
                            subject: "You have completed Donation Offer",
                            message: "Click to view"
                        }
                        notificationReceiverData = {
                            subject: "Donation Request has been Completed",
                            message: "Click to view"
                        }
                    }
                }
                if (owner == 'receive') {
                    if(indexOfStatus == 0){
                        notificationDonorData = {
                            subject: "Donation Offer has been Rejected",
                            message: "Click to view"
                        }
                        notificationReceiverData = {
                            subject: "You have rejected Donation Request",
                            message: "Click to view"
                        }
                    }
                    if(indexOfStatus == 1){
                        notificationDonorData = {
                            subject: "Donation Offer has been Completed",
                            message: "Click to view"
                        }
                        notificationReceiverData = {
                            subject: "You have completed Donation Request",
                            message: "Click to view"
                        }
                    }
                }
                const donorData = await getUserByDonationId(mappedReq.donationId)
                await sendNotificationToSingleUser(notificationDonorData, donorData)
                const receiverData = await getUserByReceiveId(mappedReq.receiveId)
                await sendNotificationToSingleUser(notificationReceiverData, receiverData)
                
            }
        }
        return true


    } catch (error) {
        throw error
    }
}

//app flow changed 25-10-2021

const getReceiveRequestByRegion = async (region) => {
    try {
        const query = {
            // region: region,
            // status: 'Pending',
            $or: [{ status: requestStatus[0] }, { status: requestStatus[2] }],
            isActive: true
        }
        const aggrArr = [
            {
                $match: query
            },
            {
                $lookup: {
                    from: 'categories',
                    localField: "categoryId",
                    foreignField: '_id',
                    as: "categoryDetails"
                }
            },
            { $unwind: "$categoryDetails" },
            {
                $lookup: {
                    from: 'categories',
                    localField: "subcategoryId",
                    foreignField: '_id',
                    as: "subCategoryDetails"
                }
            },
            { $unwind: "$subCategoryDetails" },
            {
                $addFields: {
                    categoryName: '$categoryDetails.name',
                    subCategoryName: '$subCategoryDetails.name'
                }
            },
            {
                $project: {
                    "userId": 1,
                    "categoryId": 1,
                    "subcategoryId": 1,
                    'categoryName': 1,
                    'subCategoryName': 1,
                    "description": 1,
                    "region": 1,
                    "deliveryType": 1,
                    "address": 1,
                    "cretedAt": 1,
                    "createdBy": 1,
                    "modifiedAt": 1,
                    "modifiedBy": 1,
                    "status": 1,
                }
            }

        ]
        const result = await Receive.aggregate(aggrArr).exec()
        if (result.length > 0) {
            return result
        } else {
            throw new NotFoundError('No data found')
        }

    } catch (error) {
        throw error
    }
}

const getDoanteRequestByRegion = async (region) => {
    try {
        const query = {
            $or: [{ status: requestStatus[0] }, { status: requestStatus[2] }],
            // region: region,
            // status: 'Pending',
            isActive: true
        }
        const aggrArr = [
            {
                $match: query
            },
            {
                $lookup: {
                    from: 'categories',
                    localField: "categoryId",
                    foreignField: '_id',
                    as: "categoryDetails"
                }
            },
            { $unwind: "$categoryDetails" },
            {
                $lookup: {
                    from: 'categories',
                    localField: "subcategoryId",
                    foreignField: '_id',
                    as: "subCategoryDetails"
                }
            },
            { $unwind: "$subCategoryDetails" },
            {
                $addFields: {
                    categoryName: '$categoryDetails.name',
                    subCategoryName: '$subCategoryDetails.name',
                    categoryImage: '$categoryDetails.image',
                    subCategoryImage: '$subCategoryDetails.image'
                }
            },
            {
                $project: {
                    "userId": 1,
                    "categoryId": 1,
                    "subcategoryId": 1,
                    'categoryName': 1,
                    'subCategoryName': 1,
                    "description": 1,
                    "image": 1,
                    "region": 1,
                    "deliveryType": 1,
                    "address": 1,
                    "cretedAt": 1,
                    "createdBy": 1,
                    "modifiedAt": 1,
                    "modifiedBy": 1,
                    "status": 1,
                }
            }

        ]
        const result = await Donate.aggregate(aggrArr).exec()
        if (result.length > 0) {
            for (let i = 0; i < result.length; i++) {
                result[i].image = fileConfig.donationImage.path + '/' + result[i].image
            }
            return result
        } else {
            throw new NotFoundError('No data found')
        }

    } catch (error) {
        throw error
    }
}

const getUserByReceiveId = async (id) => {
    try {
        const receiveUser = await Receive.findOne({ _id: ObjectId(id) }, { userId: 1 }).exec()
        const userId = receiveUser.userId
        const user = await User.findOne({ _id: ObjectId(userId) }, { deviceType: 1, deviceToken: 1, name: 1 }).exec()
        return user

    } catch (error) {
        throw error
    }
}

const getUserByDonationId = async (id) => {
    try {
        const donateUser = await Donate.findOne({ _id: ObjectId(id) }, { userId: 1 }).exec()
        const userId = donateUser.userId
        const user = await User.findOne({ _id: ObjectId(userId) }, { deviceType: 1, deviceToken: 1, name: 1 }).exec()
        return user

    } catch (error) {
        throw error
    }
}

const acceptReceiveRequest = async (receiveId, userData) => {
    try {
        const receiveDetailsData = await Receive.findOne({ _id: ObjectId(receiveId) }, {
            _id: 0,
            userId: 1,
            categoryId: 1,
            subcategoryId: 1,
            description: 1,
            region: 1,
            deliveryType: 1,
            address: 1
        }).exec()
        const receiveDetails = JSON.parse(JSON.stringify(receiveDetailsData))
        receiveDetails.userId = userData._id
        receiveDetails.cretedAt = new Date()
        receiveDetails.createdBy = userData._id
        receiveDetails.modifiedAt = new Date()
        receiveDetails.modifiedBy = userData._id
        receiveDetails.status = requestStatus[2]

        const donation = new Donate(receiveDetails)
        const donate = await donation.save()

        let data = {}
        data.donationId = donate._id
        data.receiveId = receiveId
        data.initiatedBy = userData._id
        await requestMappinMganager(data, "receive")
        await Receive.updateOne({ _id: ObjectId(receiveId) }, { status: 'Matched' }).exec()
        
        const donorData = await getUserByDonationId(data.donationId)
        const notificationDonorData = {
            subject: "Your Donation Offer has been matched",
            message: "Click to view"
        }
        await sendNotificationToSingleUser(notificationDonorData, donorData)
        
        const receiverData = await getUserByReceiveId(data.receiveId)
        const notificationReceiverData = {
            subject: "Your Donation Request has been matched",
            message: "Click to view"
        }
        await sendNotificationToSingleUser(notificationReceiverData, receiverData)
        return donate

    } catch (error) {
        throw error
    }
}
const acceptDonateRequest = async (donateId, userData) => {
    try {
        const donateDetailsData = await Donate.findOne({ _id: ObjectId(donateId) }, {
            _id: 0,
            userId: 1,
            categoryId: 1,
            subcategoryId: 1,
            description: 1,
            region: 1,
            deliveryType: 1,
            address: 1
        }).exec()
        const receiveDetails = JSON.parse(JSON.stringify(donateDetailsData))
        receiveDetails.userId = userData._id
        receiveDetails.cretedAt = new Date()
        receiveDetails.createdBy = userData._id
        receiveDetails.modifiedAt = new Date()
        receiveDetails.modifiedBy = userData._id
        receiveDetails.status = requestStatus[2]

        const receive = new Receive(receiveDetails)
        const rcv = await receive.save()
        let data = {}
        data.receiveId = rcv._id
        data.donationId = donateId
        data.initiatedBy = userData._id
        await requestMappinMganager(data, "donate")
        await Donate.updateOne({ _id: ObjectId(donateId) }, { status: 'Matched' }).exec()
        
        const donorData = await getUserByDonationId(data.donationId)
        const notificationDonorData = {
            subject: "Your Donation Offer has been matched",
            message: "Click to view"
        }
        await sendNotificationToSingleUser(notificationDonorData, donorData)
        
        const receiverData = await getUserByReceiveId(data.receiveId)
        const notificationReceiverData = {
            subject: "Your Donation Request has been matched",
            message: "Click to view"
        }
        await sendNotificationToSingleUser(notificationReceiverData, receiverData)
        return rcv

    } catch (error) {
        throw error
    }
}

module.exports = {
    addDonate, addRequest, reomveDonateManager, reomveRecieveManager, matchedRequesManager, requestMappinMganager, donationListManager, receiveListManager, donationDetailsManager, receiveDetailsManager, editDonationDetailsManager,
    editReceiveDetailsManager, changedonationStatusManager, changeReceiveStatusManager, matchedRequestManager, getReceiveRequestByRegion, changeRequestStatus, getDoanteRequestByRegion, getUserByReceiveId, getUserByDonationId, acceptReceiveRequest, acceptDonateRequest
}