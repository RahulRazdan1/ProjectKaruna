const express = require('express')
const router = express.Router()

const { appConfig } = require('../controllers/index.controller')

const { sendNotificationToAll, sendNotificationToSingleUser } = require('../services/notification.service')

router.use('/user', require('./userRoute'))
router.use('/category', require('./categoryRoute'))
router.use('/request', require('./requestRoute'))

router.get('/staticData/:platform', appConfig)
router.get('/sendNotification', async (req, res) => {
    let obj = {
        subject: 'Subject',
        message: 'Message'
    }
    const notify = await sendNotificationToAll(obj, 'doner')
    res.send(notify)
})
router.get('/sendSingleNotification', async (req, res) => {
    let obj = {
        subject: 'Subject',
        message: 'Message'
    }
    const notify = await sendNotificationToSingleUser(obj, { _id: "619a122bce83aae3468d7120" })
    res.send(notify)
})

module.exports = router