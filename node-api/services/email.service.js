const nodemailer = require('nodemailer')

const transporter = nodemailer.createTransport({
    service: "gmail",
    host: 'smtp.gmail.com',
    port: 587,
    auth: {
        user: "razdanapp@gmail.com",
        pass: "Wolverene99999"
    }
})
//  https://myaccount.google.com/u/4/lesssecureapps?pli=1&rapt=AEjHL4PtKBjDSKHWM8Kbq6wWM3USL8Kba1zdRZLKQCnwtFER9p5K2hP38sZ5iFM5nxi8fkno7F-tlEUsyPgZ1tXK_o32mhp35Q

//go to above link  and enable less secure app: ON

const sendSmtpEmail = (data) => {
    return new Promise((resolve, reject) => {
        message = {
            from: "razdanapp@gmail.com",
            to: data.to,
            subject: data.subject,
            html: data.body
        }

        transporter.sendMail(message, function (err, info) {
            if (err) {
                console.log('err', err)
                reject(err)
            } else {
                console.log('info', info);
                resolve(info)
            }
        })
    })

}


module.exports = { sendSmtpEmail }