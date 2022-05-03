package com.example.tutorialappfinal;

import android.content.ContentValues;
import android.content.Context;
import android.database.Cursor;
import android.database.sqlite.SQLiteDatabase;
import android.database.sqlite.SQLiteOpenHelper;
import android.util.Log;


import com.example.tutorialappfinal.QuizContract.*;
import com.example.tutorialappfinal.LogsTable.*;
import java.util.ArrayList;
import java.util.List;
public class QuizDbHelper extends SQLiteOpenHelper {





    private static final String DATABASE_NAME = "MyAwesomeQuiz.db";
    private static final int DATABASE_VERSION = 1;
    private SQLiteDatabase db;
    Logs logs;



    public QuizDbHelper(Context context) {
        super(context, DATABASE_NAME, null, DATABASE_VERSION);
    }

    @Override
    public void onCreate(SQLiteDatabase db) {
        this.db = db;
        final String SQL_CREATE_QUESTIONS_TABLE = "CREATE TABLE " +
                QuestionsTable.TABLE_NAME + " ( " +
                QuestionsTable._ID + " INTEGER PRIMARY KEY AUTOINCREMENT, " +
                QuestionsTable.COLUMN_QUESTION + " TEXT, " +
                QuestionsTable.COLUMN_OPTION1 + " TEXT, " +
                QuestionsTable.COLUMN_OPTION2 + " TEXT, " +
                QuestionsTable.COLUMN_OPTION3 + " TEXT, " +
                QuestionsTable.COLUMN_ANSWER_NR + " INTEGER," +
                QuestionsTable.COLUMN_HINT + " TEXT"+
                ")";
        db.execSQL(SQL_CREATE_QUESTIONS_TABLE);
        fillQuestionsTable();

    }

    @Override
    public void onUpgrade(SQLiteDatabase db, int oldVersion, int newVersion) {
        db.execSQL("DROP TABLE IF EXISTS " + QuestionsTable.TABLE_NAME);
        onCreate(db);
    }



    private void fillQuestionsTable() {
        Question q1 = new Question("Oval", "Oval", "Square", "Heart", 1,"A shape like that of an egg.");
        addQuestion(q1);
        Question q2 = new Question("Circle", "Star", "Triangle", "Circle", 3,"Round corners no angles curved line are equal distance from the center point");
        addQuestion(q2);
        Question q3 = new Question("Triangle", "Oval", "Square", "Triangle", 3,"A shape with three sides and three angles and all of the angles add up to 180 degrees");
        addQuestion(q3);
        Question q4 = new Question("Rectangle", "Triangle", "Rectangle", "Square", 2,"A shape with four sides and four corners. It follows that the lengths of the pairs of sides opposite each other must be equal.");
        addQuestion(q4);
        Question q5 = new Question("Square", "Square", "Rectangle", "Heart", 1,"A shapes with 4 corners and 4 sides that are all the same length");
        addQuestion(q5);
        Question q6 = new Question("diamond", "Square", "Rectangle", "Diamond", 3,"Also called a rhombus because it's sides are of equal measure and because the inside opposite angles are equal.");
        addQuestion(q6);
        Question q7 = new Question("octagon", "Octagon", "Pentagon", "Hexagon", 1,"It has eight sides and has eight angles");
        addQuestion(q7);
        Question q8 = new Question("star", "Triangle", "Star", "Diamond", 2,"Has a five corner and ten sides.");
        addQuestion(q8);
    }
    private void addQuestion(Question question) {
        ContentValues cv = new ContentValues();
        cv.put(QuestionsTable.COLUMN_QUESTION, question.getQuestion());
        cv.put(QuestionsTable.COLUMN_OPTION1, question.getOption1());
        cv.put(QuestionsTable.COLUMN_OPTION2, question.getOption2());
        cv.put(QuestionsTable.COLUMN_OPTION3, question.getOption3());
        cv.put(QuestionsTable.COLUMN_ANSWER_NR, question.getAnswerNr());
        cv.put(QuestionsTable.COLUMN_HINT, question.getHint());
        db.insert(QuestionsTable.TABLE_NAME, null, cv);
    }
    public List<Question> getAllQuestions() {
        List<Question> questionList = new ArrayList<>();
        db = getReadableDatabase();
        Cursor c = db.rawQuery("SELECT * FROM " + QuestionsTable.TABLE_NAME, null);
        if (c.moveToFirst()) {
            do {
                Question question = new Question();
                question.setPic_id(c.getInt(c.getColumnIndex(QuestionsTable._ID)));
                question.setQuestion(c.getString(c.getColumnIndex(QuestionsTable.COLUMN_QUESTION)));
                question.setOption1(c.getString(c.getColumnIndex(QuestionsTable.COLUMN_OPTION1)));
                question.setOption2(c.getString(c.getColumnIndex(QuestionsTable.COLUMN_OPTION2)));
                question.setOption3(c.getString(c.getColumnIndex(QuestionsTable.COLUMN_OPTION3)));
                question.setAnswerNr(c.getInt(c.getColumnIndex(QuestionsTable.COLUMN_ANSWER_NR)));
                question.setHint(c.getString(c.getColumnIndex(QuestionsTable.COLUMN_HINT)));
                questionList.add(question);
            } while (c.moveToNext());
        }
        c.close();
        return questionList;
    }
}
