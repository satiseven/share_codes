import { DateTime } from 'luxon'
import {
  BaseModel,
  BelongsTo,
  belongsTo,
  column,
  computed,
  HasMany,
  hasMany,
} from '@ioc:Adonis/Lucid/Orm'
import Email from './Email'
import ReadEmailActivity from './ReadEmailActivity'
import { isRelationshipPreloaded } from 'App/Helpers/model'
import User from './User'

export default class ReadEmail extends BaseModel {
  @column({ isPrimary: true })
  public id: string

  @column()
  public emailId: string

  @column()
  public userId: string

  @column()
  public device: string

  @column()
  public isDeleted: boolean = false

  @computed()
  // TODO count from this.activities with type read / reopen
  public get readTimes() {
    isRelationshipPreloaded(this, 'activities')
    return this.activities.length
  }

  @column.dateTime({ autoCreate: true })
  public createdAt: DateTime

  @column.dateTime({ autoCreate: true, autoUpdate: true })
  public updatedAt: DateTime

  @belongsTo(() => Email)
  public email: BelongsTo<typeof Email>

  @hasMany(() => ReadEmailActivity)
  public activities: HasMany<typeof ReadEmailActivity>

  @belongsTo(() => User)
  public user: BelongsTo<typeof User>

  @computed()
  public get date() {
    return this.createdAt.toLocaleString(DateTime.DATE_MED)
  }

  @computed()
  public get time() {
    return this.createdAt.toRelative()
  }

  public get serializedReadEmailInfo() {
    isRelationshipPreloaded(this, 'email')
    isRelationshipPreloaded(this, 'activities')

    const { id, email, emailId, readTimes, device, date, time, activities } = this

    return {
      id,
      emailId,
      readTimes,
      device,
      recipient: email.recipient,
      subject: email.subject,
      gmailMessageId: email.gmailMessageId,
      gmailThreadId: email.gmailThreadId,
      date,
      time,
      activities: activities.map((activity) => activity.serializedReadEmailActivity),
    }
  }
}
