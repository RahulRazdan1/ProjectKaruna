const { Schema, Types, model } = require('mongoose')
const { ObjectId } = Types
const reqStatus = require('../config/modelConfig.json').request
const receiveSchema = new Schema({
    userId: {
        type: ObjectId,
        required: true
    },
    categoryId: {
        type: ObjectId,
        required: true
    },
    subcategoryId: {
        type: ObjectId,
        required: true
    },
    description: {
        type: String,
        default: ""
    },
    region: {
        type: String,
        required: true
    },
    deliveryType: {
        type: String,
        required: true
    },
    address: {
        type: String,
        required: true
    },
    cretedAt: {
        type: Date,
        default: Date.now()
    },
    createdBy: {
        type: ObjectId
    },
    modifiedAt: {
        type: Date,
        default: Date.now()
    },
    modifiedBy: {
        type: ObjectId,
        default: null
    },
    isActive: {
        type: Boolean,
        default: true
    },
    status: {
        type: String,
        default: reqStatus[0]
    }
})

receiveSchema.pre('updateOne', async function (next) {
    delete this._update.createdBy
    this._update.modifiedAt = Date.now()
    next()
})

const donate = model('Receive', receiveSchema)
module.exports = donate