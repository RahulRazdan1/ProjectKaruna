const { body, param } = require('express-validator')
const { ObjectId } = require('mongoose').Types
const Donate = require('../models/donateRequest.model')
const Recieve = require('../models/receiveRequest.model')
const User = require('../models/user.model')
const RequestMap = require('../models/requestMap.model')
const staticData = require('../util/staticData.json')

const requestStatus = require('../util/staticData.json').requestStatus


const validateDonateInputs = () => {
    return [
        body('categoryId').exists().withMessage("Category Id required").isMongoId().withMessage("Wrong Category Id format"),
        body('subcategoryId').exists().withMessage("Sub-Category Id required").isMongoId().withMessage("Wrong Category Id format"),
        body('description').exists().withMessage("description is required"),
        body('region').exists().withMessage("Region required").isString().withMessage("Please provide a region").custom(value => {
            if (staticData.regions.includes(value)) {
                return Promise.resolve(true)
            } else {
                return Promise.reject("Provided region is not valid")
            }
        }),
        body('deliveryType').exists().withMessage("Delivery Type required").isString().withMessage("Delivery Type Wrong").custom(value => {
            if (staticData.deliveryTypes.includes(value)) {
                return Promise.resolve(true)
            } else {
                return Promise.reject("Provided delivery type is not valid")
            }
        }),
        body('address').exists().withMessage("Addressrequired")
    ]
}

const validateDeleteDonate = () => {
    return [
        param('requestId').exists().withMessage('Please Pass request Id to delete the request').isMongoId().withMessage('Invalid request ID').custom(value => {
            return new Promise(async (resolve, reject) => {
                try {
                    let doc = await Donate.findOne({ _id: ObjectId(value), isActive: true }).exec()
                    if (doc && doc !== null) {
                        resolve(true)
                    } else {
                        reject('No Donate request found with corresponding ID')
                    }
                } catch (error) {
                    reject(error)
                }
            })
        })
    ]
}
const validateDeleteRecieve = () => {
    return [
        param('requestId').exists().withMessage('Please Pass request Id to delete the request').isMongoId().withMessage('Invalid request ID').custom(value => {
            return new Promise(async (resolve, reject) => {
                try {
                    let doc = await Recieve.findOne({ _id: ObjectId(value), isActive: true }).exec()
                    if (doc && doc !== null) {
                        resolve(true)
                    } else {
                        reject('No Recieve request found with corresponding ID')
                    }
                } catch (error) {
                    reject(error)
                }
            })
        })
    ]
}

const validateDonationUser = () => {
    return [
        param('userId').isMongoId().withMessage("Invalid User ID").custom(value => {
            return new Promise(async (resolve, reject) => {
                try {
                    let doc = await User.findOne({ _id: ObjectId(value) }).exec()
                    if (doc && doc !== null) {
                        if (doc.userType == "doner") {
                            resolve(true)
                        } else {
                            reject(`You are trying with receiver's ID`)
                        }

                    } else {
                        reject('No User ID found with corresponding ID')
                    }
                } catch (error) {
                    reject(error)
                }
            })
        })
    ]
}

const validateReceiverUser = () => {
    return [
        param('userId').isMongoId().withMessage("Invalid User ID").custom(value => {
            return new Promise(async (resolve, reject) => {
                try {
                    let doc = await User.findOne({ _id: ObjectId(value) }).exec()
                    if (doc && doc !== null) {
                        if (doc.userType == "receiver") {
                            resolve(true)
                        } else {
                            reject(`You are trying with doner's ID`)
                        }

                    } else {
                        reject('No User ID found with corresponding ID')
                    }
                } catch (error) {
                    reject(error)
                }
            })
        })
    ]
}


const validatedonationId = () => {
    return [
        param('id').exists().withMessage('Please Pass the donation request Id').isMongoId().withMessage('Invalid request ID').custom(value => {
            return new Promise(async (resolve, reject) => {
                try {
                    let doc = await Donate.findOne({ _id: ObjectId(value), isActive: true }).exec()
                    if (doc && doc !== null) {
                        resolve(true)
                    } else {
                        reject('No Donate request found with corresponding ID')
                    }
                } catch (error) {
                    reject(error)
                }
            })
        })
    ]
}
const validateReceiveId = () => {
    return [
        param('id').exists().withMessage('Please Pass the receive request Id').isMongoId().withMessage('Invalid request ID').custom(value => {
            return new Promise(async (resolve, reject) => {
                try {
                    let doc = await Recieve.findOne({ _id: ObjectId(value), isActive: true }).exec()
                    if (doc && doc !== null) {
                        resolve(true)
                    } else {
                        reject('No Recieve request found with corresponding ID')
                    }
                } catch (error) {
                    reject(error)
                }
            })
        })
    ]
}

const validateDonationStatus = () => {
    return [
        param('id').exists().withMessage('Please Pass the receive request Id').isMongoId().withMessage('Invalid request ID').custom(value => {
            return new Promise(async (resolve, reject) => {
                try {
                    let doc = await Donate.findOne({ _id: ObjectId(value), isActive: true }).exec()
                    if (doc && doc !== null) {
                        resolve(true)
                    } else {
                        reject('No Recieve request found with corresponding ID')
                    }
                } catch (error) {
                    reject(error)
                }
            })
        }),
        body('status').exists().withMessage(`status key required in body param`).custom((value, { req }) => {
            return new Promise(async (resolve, reject) => {
                try {
                    let doc = await Donate.findOne({ _id: ObjectId(req.params.id), isActive: true }).exec()
                    if (doc && doc !== null) {
                        console.log(doc)
                        let checkStatus = requestStatus.findIndex(item => value.toLowerCase() === item.toLowerCase());
                        if (checkStatus == -1) {
                            reject('Invalid status in body')
                        } else {
                            if (requestStatus[1].toLowerCase() == doc.status.toLowerCase()) {
                                reject(`Can not change the status after completion`)
                            } else {
                                resolve(true)
                            }
                        }
                    } else {
                        resolve(true)
                    }
                } catch (error) {
                    reject(error)
                }
            })

        })
    ]
}

const validateReceiveStatus = () => {
    return [
        param('id').exists().withMessage('Please Pass the receive request Id').isMongoId().withMessage('Invalid request ID').custom(value => {
            return new Promise(async (resolve, reject) => {
                try {
                    let doc = await Recieve.findOne({ _id: ObjectId(value), isActive: true }).exec()
                    if (doc && doc !== null) {
                        resolve(true)
                    } else {
                        reject('No Recieve request found with corresponding ID')
                    }
                } catch (error) {
                    reject(error)
                }
            })
        }),
        body('status').exists().withMessage(`status key required in body param`).custom((value, { req }) => {
            return new Promise(async (resolve, reject) => {
                try {
                    let doc = await Recieve.findOne({ _id: ObjectId(req.params.id), isActive: true }).exec()
                    if (doc && doc !== null) {
                        console.log(doc)
                        let checkStatus = requestStatus.findIndex(item => value.toLowerCase() === item.toLowerCase());
                        if (checkStatus == -1) {
                            reject('Invalid status in body')
                        } else {
                            if (requestStatus[1].toLowerCase() == doc.status.toLowerCase()) {
                                reject(`Can not change the status after completion`)
                            } else {
                                resolve(true)
                            }
                        }
                    } else {
                        resolve(true)
                    }
                } catch (error) {
                    reject(error)
                }
            })

        })
    ]
}

const validateSendRequest = () => {
    return [
        body('donationId').exists().withMessage('Donate Id required').isMongoId().withMessage('Invalid donation id'),
        body('receiveId').exists().withMessage('Donate Id required').isMongoId().withMessage('Invalid receive id')
    ]
}

module.exports = { validateDonateInputs, validateDeleteDonate, validateDeleteRecieve, validateDonationUser, validateReceiverUser, validatedonationId, validateReceiveId, validateDonationStatus, validateReceiveStatus, validateSendRequest }