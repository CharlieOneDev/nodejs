// server.js
const express = require('express');
const nodemailer = require('nodemailer');
const cors = require('cors');
const app = express();

app.use(cors());
app.use(express.json());

const transporter = nodemailer.createTransport({
  service: 'gmail', // 或使用其他 SMTP 服务
  auth: {
    user: 'customer@daion.co.jp', // 你的邮箱
    pass: 'yourpassword'    // 应用专用密码（或 SMTP 密码）
  }
});

app.post('/api/contact', async (req, res) => {
  const { name, email, message } = req.body;

  const footer = "\n------------\n大恩家具株式会社\nhttps://www.daion.co.jp/\n------------\n";

  // 给客户的自动回复
  const userMailOptions = {
    from: 'customer@daion.co.jp',
    to: email,
    subject: 'お問い合わせ有難うございます\n',
    text: `お問い合わせ有難うございます。\n以下の内容で承りました。\n\nお名前: ${name}\nメールアドレス: ${email}\nお問い合わせ内容:\n${message}\n${footer}`
  };

  // 发给公司
  const companyMailOptions = {
    from: 'customer@daion.co.jp',
    to: 'customer@daion.co.jp',
    subject: 'お問い合わせがありました\n',
    text: `お名前: ${name}\nメールアドレス: ${email}\nお問い合わせ内容:\n${message}\n${footer}`
  };

  try {
    await transporter.sendMail(userMailOptions);
    await transporter.sendMail(companyMailOptions);
    res.status(200).send({ message: '送信完了しました' });
  } catch (error) {
    console.error(error);
    res.status(500).send({ message: '送信に失敗しました' });
  }
});

app.listen(process.env.PORT || 3000, () => {
  console.log('Server running...');
});
