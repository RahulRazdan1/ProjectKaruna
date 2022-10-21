const mongoose = require("mongoose");
const Schema = mongoose.Schema;
const { ObjectId } = mongoose.Types;

const { encryptText } = require("../helper/encryptDecrypt");

const userSchema = new Schema({
	firstName: {
		type: String,
		required: true,
	},
	lastName: {
		type: String,
		required: true,
	},
	email: {
		type: String,
		required: true,
	},
	mobile: {
		type: String,
		required: true,
	},
	password: {
		type: String,
		required: true,
	},
	userType: {
		type: String,
		required: true,
	},
	region: {
		type: String,
		required: true,
	},
	address: {
		type: String,
		required: true,
		trim: true,
	},
	image: {
		type: String,
		default: "default_user_avatar.png",
	},
	deviceType: {
		type: String,
		required: true,
	},
	deviceToken: {
		type: String,
		required: true,
	},
	createdAt: {
		type: Date,
		default: new Date(),
	},
	createdBy: {
		type: ObjectId,
		default: null,
	},
	modifiedAt: {
		type: Date,
		default: new Date(),
	},
	deviceUpdatedAt: {
		type: Date,
		default: new Date(),
	},
	modifiedBy: {
		type: ObjectId,
		default: null,
	},
	isActive: {
		type: Boolean,
		default: true,
	},
});

userSchema.pre("save", async function (next) {
	const userData = this;

	if (this.isModified("password") || this.isNew) {
		let pw = await encryptText(userData.password);
		userData.password = pw;
		next();
	} else {
		return next();
	}
});


const user = mongoose.model("Users", userSchema);
module.exports = user;