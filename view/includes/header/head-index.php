<style>

    .defbox {

        background-color: white;
        box-shadow: 3px 3px 3px lightgrey;

        width: 90%;
        color: #4d4d4d;
        border-radius: 8px;


        border: 1px solid white;
        border-radius: 8px;

    }

    .defbox:hover {

        border: 1px solid gold;
        transition: all .25s;
    }

    .defbox .defcard {

        padding: 30px;
        height: 90%;
        transition: all .25s;


    }

    .defbox .defcard .deftitle {
        display: flex;
        justify-content: space-between;
    }

    .defbox .defcontent{

        margin: 0 10px 0 10px;

    }

    .defbox .defcontentitem{

        background-color: whitesmoke;
        border-radius: 8px;
        box-shadow: 2px 2px 2px lightgrey;

        padding: 12px;

        margin-top: 10px;
        display: flex;

    }

    .defbox .deffooter{
        display: flex;
        justify-content: space-between;

    }


    .cl-pre-span i {
        font-size: 14px;
    }

    .cl-l {
        background-color: rgb(60, 63, 65);
        flex-direction: column;
        width: 20px
    }

    .cl-l i {
        font-size: 12px;
        line-height: 14px;
    }

    .cl-l .cl-l-red, .cl-l .cl-l-yellow, .cl-l .cl-l-green {
        color: rgb(127, 127, 127);
    }

    .cl-l .cl-l-red.cl-l-active {
        color: red;
    }

    .cl-l .cl-l-yellow.cl-l-active {
        color: rgb(255, 255, 0)
    }

    .cl-l .cl-l-green.cl-l-active {
        color: rgb(0, 255, 0)
    }

    .fc-timegrid-slots {
        display: none;
    }

    .main-defbox{
        padding: 30px;
        width: 95%;
        display: flex;
        justify-content: center;
        border: 1px solid gold;

    }

    @media only screen and (max-width: 600px) {
        .main-defbox {
            width: 90%;
        }
    }

</style>