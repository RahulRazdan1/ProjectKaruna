const { Schema, Types, model } = require('mongoose')
const { ObjectId } = Types
const reqStatus = require('../config/modelConfig.json').request
const donateSchema = new Schema({
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
    image: {
        type: String,
        required: true,
        default: "no img"
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
        default: new Date()
    },
    createdBy: {
        type: ObjectId
    },
    modifiedAt: {
        type: Date,
        default: new Date()
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

donateSchema.pre('updateOne', async function (next) {
    delete this._update.createdBy
    this._update.modifiedAt = Date.now()
    next()
})

const donate = model('Donates', donateSchema)
module.exports = donate