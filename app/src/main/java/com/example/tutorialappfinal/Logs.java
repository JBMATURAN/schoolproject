package com.example.tutorialappfinal;

public class Logs {
    private int id;
    private String answer;
    private int hint;
    private int topics;
    private String time;

    public Logs(){
        id=getId();
        answer=getAnswer();
        hint=getHint();
        topics=getTopics();
        time=getTime();
    }
    public Logs(int id, String answer,int hint,int topics,String time) {
        this.id = id;
        this.answer = answer;
        this.hint = hint;
        this.topics = topics;
        this.time = time;
    }

    public int getId() { return id; }

    public void setId(int id) { this.id = id; }

    public String getAnswer() { return answer; }

    public void setAnswer(String answer) { this.answer = answer; }

    public int getHint() { return hint; }

    public void setHint(int hint) { this.hint = hint; }

    public int getTopics() { return topics; }

    public void setTopics(int topics) { this.topics = topics; }

    public String getTime() { return time; }

    public void setTime(String time) { this.time = time; }
}
