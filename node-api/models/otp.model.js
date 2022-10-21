const mongoose = require('mongoose');
const Schema = mongoose.Schema;


const otpSchema = new Schema({
    userId: {
        type: String,
        required: true
    },
    otp: {
        type: String,
        required: true,
        maxlength: 6,
        minlength: 6
    },
    createdTime: {
        type: Date,
        required: true
    },
    expireTime: {
        type: Date,
        required: true
    },

})

const otp = mongoose.model('Otp', otpSchema)
module.exports = otp