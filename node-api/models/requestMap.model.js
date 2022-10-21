const { Schema, Types, model } = require('mongoose')
const reqStatus = require('../util/staticData.json').requestStatus
const { ObjectId } = Types

const mappingSchema = new Schema({
    donationId: {
        type: ObjectId,
        required: true
    },
    receiveId: {
        type: ObjectId,
        required: true
    },
    initiatedBy: {
        type: ObjectId,
        required: true,
    },
    region: {
        type: String,
        required: true
    },
    createdAt: {
        type: Date,
        default: Date.now()
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

mappingSchema.pre('updateOne', async function (next) {
    delete this._update.createdBy
    this._update.modifiedAt = Date.now()
    next()
})

const mapping = model('requestMapping', mappingSchema)
module.exports = mapping