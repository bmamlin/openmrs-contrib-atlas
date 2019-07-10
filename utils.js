var schedule = require('node-schedule');
var nodemailer = require("nodemailer");

module.exports = {

    /* Middleware to check whether the user is logged in */
    isAuthenticated : function(req, res, next) {
        if(req.session.authenticated) {
            return next();
        } else {
            res.redirect("/login");
        }
    },
    
    /* Middleware to check whether the user is authenticated as admin */
    isAdmin: function(req, res, next) {
        if(req.session.authenticated && req.session.user.admin) {
            return next();
        } else {
            res.send(401);
        }
    },

    scheduleMails: function(connection) {

        let transporter = nodemailer.createTransport({
            host: process.env.MAIL_HOST,
            port: process.env.MAIL_PORT,
            use_authentication: Boolean(process.env.MAIL_AUTH),
            auth: {
                user: process.env.MAIL_USER,
                pass: process.env.MAIL_PASS
            }
        });

        schedule.scheduleJob('5 * * * * *', function(){

            mailOptions = {
                from: "'Sai Sandeep' <saisandeep20000@gmail.com>", // sender address
                to: "sandeep.mutyala@outlook.com", // list of receivers
                subject: "Hello ✔", // Subject line
                text: "Hello world?", // plain text body
                html: "<b>Hello world?</b>" // html body
            }
            // send mail with defined transport object
            let info = transporter.sendMail(mailOptions, function(error, info) {
                if (error) {
                    console.log(error);
                } else {
                    console.log('Email sent: ' + info.response);
                }
            });

        });

    }
};