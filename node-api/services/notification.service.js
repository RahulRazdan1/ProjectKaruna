const FCM = require("fcm-node");
const firebaseConfig = require("../config/appConfig.json").FIREBASE;
const fcm = new FCM(firebaseConfig.SERVERKEY);

const { getAllUserDeviceDetails, getDeviceDetails } = require('../manager/user.manager')

const sendNotificationToAll = async (notificationData, userType) => {

    const deviceDetails = await getAllUserDeviceDetails(userType)
    // const iosDeviceDetails = deviceDetails.filter(e => e.deviceType == 'ios')
    // const iosDeviceTokens = iosDeviceDetails.map(el => el.deviceToken)
    // const androidDeviceDetails = deviceDetails.filter(e => e.deviceType == 'android')
    // const androidDeviceTokens = androidDeviceDetails.map(el => el.deviceToken)
    const deviceTokens = deviceDetails.map(el => el.deviceToken)
    // console.log(deviceTokens)
    const message = {
        registration_ids: deviceTokens,
        notification: {
            title: notificationData.subject,
            body: notificationData.message
        },
        data: {
            "title": notificationData.subject,
            "body": notificationData.message
        },


    };

    await fcm.send(message, (error, response) => {
        if (error) {
            console.log(error)
        } else {
            console.log(response)
        }
    })
    return ('notification sent')

};

const sendNotificationToSingleUser = async (notificationData, userData) => {
    console.log(userData)
    const deviceDetails = await getDeviceDetails(userData)
    console.log(deviceDetails)
    const message = {
        to: deviceDetails.deviceToken,
        notification: {
            title: notificationData.subject,
            body: notificationData.message
        },
        data: {
            "title": notificationData.subject,
            "body": notificationData.message
        },
    }
    await fcm.send(message, (error, response) => {
        console.log('error', error)
        console.log('response', response)
    })
    return ('notification sent')
};

module.exports = { sendNotificationToAll, sendNotificationToSingleUser };
