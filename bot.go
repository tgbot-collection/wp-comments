package main

import (
	"bytes"
	"encoding/base64"
	"encoding/json"
	"flag"
	"fmt"
	tb "gopkg.in/tucnak/telebot.v2"
	"io/ioutil"
	"log"
	"math/rand"
	"net/http"
	"os"
	"strings"
	"time"
)

const version = "0.0.1"

var (
	h, v, f bool
	c       string
)

type config struct {
	Username string `json:"username"`
	Password string `json:"password"`
	Url      string `json:"url"`
	Token    string `json:"token"`
	Uid      int64  `json:"uid,string"`
	Admin    int    `json:"admin"`
	Tail     string `json:"tail,omitempty"`
}

func init() {
	flag.BoolVar(&h, "h", false, "this help")
	flag.BoolVar(&v, "v", false, "show version and exit")
	flag.BoolVar(&f, "f", false, "force to run even on http sites.")
	flag.StringVar(&c, "c", "config.json", "set configuration `file`")

}
func getArgs() config {
	flag.Parse()

	if v {
		fmt.Println(version)
		os.Exit(0)
	}
	if h {
		flag.Usage()
		os.Exit(0)
	}
	var configFile = c
	configData, err := readConfig(configFile)

	if err != nil {
		fmt.Printf("config file is corrupted or not found. \n%v\n", err)
		flag.Usage()
		os.Exit(2)

	} else if !f && !strings.HasPrefix(configData.Url, "https://") {
		fmt.Println("Your website is not https. Exit now.")
		fmt.Println("Please use -f to force start.")
		os.Exit(1)
	} else {
		fmt.Println("Okay let's roll😄")
	}
	return configData

}

func readConfig(cp string) (config, error) {

	jsonFile, err := os.Open(cp)
	if err != nil {
		return config{}, err
	}
	defer jsonFile.Close()

	var conf config
	byteValue, _ := ioutil.ReadAll(jsonFile)
	err = json.Unmarshal(byteValue, &conf)
	return conf, err
}

func randomEmoji() string {

	reasons := []string{"🙃", "🤨", "🤪", "😒", "🙂", "🥶", "🤔", "😶", "😐"}
	rand.Seed(time.Now().Unix())
	return reasons[rand.Intn(len(reasons))]
}

func replyComment(msg, reply string, conf config) (result string) {
	type postFormat struct {
		Author          int    `json:"author"`
		AuthorUserAgent string `json:"author_user_agent"`
		Content         string `json:"content"`
		Parent          string `json:"parent"`
		Post            string `json:"post"`
	}
	reply += "\n\n\t\t" + conf.Tail
	g := strings.Split(strings.Split(msg, "id: ")[1], ",")
	pid, cid := g[0], g[1]
	post := postFormat{
		Author:          1,
		AuthorUserAgent: "Telegram Bot by BennyThink",
		Content:         reply,
		Parent:          pid,
		Post:            cid,
	}
	bytesData, err := json.Marshal(post)
	if err != nil {
		fmt.Println(err.Error())
	}

	data := bytes.NewReader(bytesData)
	wpApi := conf.Url + "wp-json/wp/v2/comments"
	request, err := http.NewRequest("POST", wpApi, data)
	auth := []byte(fmt.Sprintf("%s:%s", conf.Username, conf.Password))
	request.Header.Set("Content-Type", "application/json;charset=UTF-8")
	request.Header.Set("Authorization", "Basic "+base64.StdEncoding.EncodeToString(auth))
	client := http.Client{}
	resp, err := client.Do(request)
	if err != nil {
		fmt.Println(err.Error())
	}
	var body struct {
		Message string `json:"message"`
	}
	if resp.StatusCode == 201 {
		result = "ok"
	} else {
		json.NewDecoder(resp.Body).Decode(&body)
		result = body.Message
	}
	return

}

func bot(conf config) {
	var owner = &tb.Chat{ID: conf.Uid}
	b, err := tb.NewBot(tb.Settings{
		Token:  conf.Token,
		Poller: &tb.LongPoller{Timeout: 10 * time.Second},
	})

	if err != nil {
		log.Fatal(err)
		return
	}
	b.Handle(tb.OnText, func(m *tb.Message) {
		if m.Chat.ID != conf.Uid {
			b.Notify(m.Sender, "Typing")
			b.Send(m.Sender, randomEmoji())
			b.Send(m.Sender, "拒绝调戏。有问题请联系@BennyThink")

		} else if m.ReplyTo == nil {
			b.Notify(m.Sender, "Typing")
			b.Send(m.Sender, randomEmoji())
		} else {
			comment := m.ReplyTo.Text
			reply := m.Text
			resp := replyComment(comment, reply, conf)
			b.Send(owner, resp)

		}
	})

	b.Start()
}
func main() {
	c := getArgs()
	bot(c)
}
